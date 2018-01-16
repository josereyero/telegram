<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramProcess
 */

namespace Drupal\telegram_cli;

use Psr\Log\LoggerInterface;

class TelegramProcess {

  // Running parameters.
  protected $params;

  // Running process
  protected $process;

  // Pipes for input / output streams
  protected $pipes;

  // Input / Output history.
  protected $output;
  protected $input;
  protected $errors;


  // Helper variables for processing
  protected $lastCommand;
  protected $timeout;

  /**
   * @var \Drupal\telegram\TelegramLogger
   */
  protected $logger;

  /**
   * Class constructor.
   *
   * @param array $params
   *   Mixed process parameters.
   * @param TelegramLogger $logger
   *   Logging interface.
   */
  public function __construct(array $params, LoggerInterface $logger) {
    // Add some defaults
    $params += array(
      'command' => '/usr/local/bin/telegram',
      'keyfile' => '/etc/telegram/server.pub',
      'configfile' => '/etc/telegram/telegram.conf',
      'homepath' => '/tmp/telegram',
      // Timeout for locking operations (read), in seconds.
      'timeout' => 10,
    );
    // Initialize variables.
    $this->params = $params;
    $this->logger = $logger;
  }

  /**
   * Get command line.
   */
  public function getCommandLine() {
    return $this->params['command'] .
      ' -N' . // Print out message numbers
      ' -c ' . $this->params['configfile'] .
      ' -k ' . $this->params['keyfile'];
  }

  /**
   * Get proc status.
   *
   * @see proc_get_status()
   *
   * @return array|FALSE
   *   Status array if process is open, FALSE if not.
   */
  public function getStatus() {
    if (isset($this->process)) {
      return proc_get_status($this->process);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check whether the process is running.
   *
   * @return boolean
   *   TRUE if running, false if not.
   */
  public function isStarted() {
    return !empty($this->process) && is_resource($this->process);
  }

  /**
   * Execute command and get response.
   *
   * For a full list of commands and parameters see
   * https://github.com/vysheng/tg
   *
   * @param string $command
   *   Telegram CLI command to execute.
   * @param string $arg1, $arg2...
   *   Optional, variable number of arguments for the command.
   */
  public function execCommand($command, $args = NULL) {
    // Build and sanitize parameters
    if ($args) {
      $params = implode(' ', $args);
      // @todo Better sanitize params.
      $params = str_replace("\n", ' ', $params);
      $command .= ' ' . trim($params);
    }
    // Flush output, there may be responses to previous commands.
    $this->flush();
    // Save last command executed.
    $this->lastCommand = $command;
    // Write command to the process input.
    $this->logger->debug("execCommand [$command]");
    $this->write($command . "\n");

    $this->wait(100);
    // Get command response.
    return $this->getResponse();
  }

  /**
   * Get response.
   *
   * Cycle until response is got, wait for prompt.
   */
  function getResponse() {
    $timeout = $this->setTimeout();
    if (isset($this->lastCommand)) {
      $command = $this->lastCommand;
      unset($this->lastCommand);
    }

    while (!$this->output && $this->checkTimeout(__FUNCTION__)) {
      $response = $this->readUntil('>', $timeout);
      $this->logger->debug('getResponse: @response', ['@response' => $response]);
      // If the command is part of the response, remove it.
      if (isset($command) && ($index = array_search($command, $response, TRUE)) !== FALSE) {
        unset($response[$index]);
        $command = NULL;
      }
      $this->output = $response;
    }
    // For some commands, like contact_list, there may be more lines.
    while ($this->readAll()) {
      $this->wait();
    }

    return $this->output;
  }

  /**
   * Parse response and return.
   *
   * @param string $pattern
   *   Regular expression to match results.
   *   @param array $key
   *   Array with the key of name array
   *
   * @return array
   *   Matching lines as resulting arrays from preg_match.
   */
  function parseResponse($pattern, $key = NULL) {
  	$this->logger->debug("parseResponse: $pattern");
    if (!isset($this->output)) {
      $this->getResponse();
    }

    if (!empty($this->output)) {
      $result = array();

      $resultmed = array();
      foreach ($this->output as $index => $line) {
        $matches = array();

        foreach ($pattern as $patterns) {
          if (preg_match($patterns, $line, $matches)) {
            // Yeah, line matches expected format.
            // First add it to result.
            $resultmed[] = array(
              'matches' => $matches,
            );
            // Remove it from buffer.
            unset($this->output[$index]);
          }
          else {
            // Line doesn't match, keep it.
            // so do nothing.
          }
        }
      }

      $countresult = count($resultmed);
		  (isset($key)) ? $countkey = count($key):'';
		  for ($i=0 ; $i<$countresult ; $i++) {
		  	(!isset($key)) ? $countkey = count($resultmed[$i]['matches']):'';
		    $z=0;
    		foreach ($resultmed[$i]['matches'] as $lines) {
    		  if ($z == $countkey){
    		    $z=0;
    		  }
    		  (isset($key)) ? $result[$i][$key[$z]] = $lines : $result[$i][$z] = $lines;
    		  $z++;
    	  }
      }
      $this->logger->debug("parseResponse results: @result", ['@result' => $result]);
      // Returns resulting array from all matching lines
      return $result;
    }
    else {
      $this->logger->debug("parseResponse empty");
      // No output, return empty array.
      return array();
    }

  }

  /**
   * Read multiple lines from command output and store them.
   *
   * This one filters out blank lines, prompt '>', etc...
   *
   * @return string|FALSE
   *   Full contents of command output.
   */
  protected function readAll() {
    fflush($this->pipes[1]);
    $string = stream_get_contents($this->pipes[1]);
    if ($string) {
      $string = $this->filter($string);
      foreach (explode("\n", $string) as $line) {
        $line = trim($line);
        if ($line && $line !== '>') {
          $this->output[] = $line;
        }
      }
    }
    $this->logger->debug("readAll: $string");
    return $string;
  }

  /**
   * Low level string read. Some wrapper around fgets()
   *
   * This is tricky because Telegram CLI output lines may
   * end with newline or line feed. Or when reading until
   * the prompt '>' there may be no new line.
   *
   * Sometimes we get the string "\x1b[0bm" before the prompt.
   *
   * @param string $stop
   *   Aditional stop char besides end of line markers
   * @param int $timeout
   *   Timeout, unix time in seconds.
   */
  protected function readLine($stop = NULL, $timeout = NULL) {
    fflush($this->pipes[1]);
    $timeout = $this->setTimeout($timeout);
    $string = $this->readString();
    // Read string until we get something or reach timeout.
    while (!$string && $this->checkTimeout(__FUNCTION__)) {
      $this->wait();
      $string = $this->readString($stop);
    }
    // We reach here because end of line or timeout
    //$string = $this->filter($string);
    $this->logger->debug('readLine: @string', ['@string' => $string]);
    return $string;
  }

  /**
   * Read and filter strings.
   *
   * This reads strings one at a time until there's nothing to read or
   * it gets a new line character.
   */
  protected function readString($stop = NULL) {
    $string = $char = '';
    while ($char !== FALSE && $string !== $stop) {
      $char = fgetc($this->pipes[1]);
      if ($char == "\n" || $char == "\r") {
        // End of line, force return.
        $char = FALSE;
      }
      else {
        $string .= $char;
        // Filter out some special strings
        // Remove blanks at the beginning of a line.
        if ($string == ' ' || $string == "\x1b[0m") {
          $string = '';
        }
      }
    }
    $string = $this->filter($string);
    $this->logger->debug('readString: @string', ['@string' => $string]);
    return $string;
  }

  /**
   * Read until we find some (full line) string.
   *
   * @return array
   *   Array of (trimmed) string lines before the stop char.
   */
  protected function readUntil($stop = '>', $timeout = NULL) {
    $timeout = $this->setTimeout($timeout);
    $this->logger->debug("readUntil $stop");
    $lines = array();
    $string = '';
    while ($string !== $stop && $this->checkTimeout(__FUNCTION__)) {
      if ($string) {
        $lines[] = $string;
      }
      $string = $this->readLine($stop, $timeout);
    }
    return $lines;
  }

  /**
   * Write to the input stream.
   *
   * @param string $string
   *   String to write.
   * @param boolean $flush
   *   Whether to flush the stream after writing.
   */
  protected function write($string, $flush = TRUE) {
    $this->logger->debug("writeString: $string");
    $result = fwrite($this->pipes[0], $string);
    if ($flush) {
      fflush($this->pipes[0]);
    }
    return $result;
  }

  /**
   * Filter string, remove ANSI color codes.
   */
  protected function filter($string) {
    // Filter control codes in output
   	$string = preg_replace('/\x1B\[[0-9;]*[mK]/u', '', $string);
    return trim($string);
  }

  /**
   * Flush streams before issuing a command.
   */
  protected function flush() {
    fflush($this->pipes[1]);
    $content = stream_get_contents($this->pipes[1]);
    $this->logger->debug('flush @content', ['@content' => $content]);
    // Get rid of output.
    $this->output = array();
  }

  /**
   * Start process.
   *
   * We try to start the process only once.
   *
   * @return boolean
   *   TRUE if the program started successfully.
   */
  public function start() {
    if (!isset($this->process)) {
      // Initialize variables and pipes for input/output/errors
      $this->pipes = array();
      $descriptorspec = array(
         0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
         1 => array("pipe", "w+"),  // stdout is a pipe that the child will write to
         2 => array("pipe", "w+"),  // stderr
      );

      $this->process = proc_open($this->getCommandLine(), $descriptorspec, $this->pipes, $this->params['homepath']);

      if (!is_resource($this->process)) {
        // Process failed, close everything.
        $this->logger->error('Failed to start Telegram CLI');
      }
      else {
        // Log initial status and pid.
        $status = $this->getStatus();
        $this->logger->debug('Process status @status', ['@status' => $status]);
        $pid = $status['pid'];
        $this->logger->info('Process started with pid @pid', ['@pid' => $pid]);

        // Use non blocking streams.
        stream_set_blocking($this->pipes[1], 0);
        stream_set_blocking($this->pipes[2], 0);

        // Check for errors during start up.
        if ($this->getErrors()) {
          $this->logger->error('Startup errors in Telegram CLI');
        }
        else {
          // Read first four lines that are credits and license messages.
          $this->readUntil('>');
          // Should the first dialog list should stay there
          // just in case the client wants to read it.
          $this->readUntil('>');
        }
      }

      // Final check for errors, clean up if any
      $errors = $this->getErrors();

      if (!is_resource($this->process) || $errors) {
        $this->stop();
        // Mark process so we don't try to start it again.
        $this->process = FALSE;
      }
    }
    return $this->isStarted();
  }

  /**
   * Exit process (send quit command).
   */
  public function stop() {
    if (isset($this->process) && $this->process !== FALSE) {
      $this->logger->info('Closing process');
      if (is_resource($this->process)) {
        $this->write("quit\n");
        $this->wait(100);
      }
      // Flush output so we can see any other message.
      $this->flush();
      // Clean up all resources.
      if (is_array($this->pipes)) {
        foreach ($this->pipes as $pipe) {
          fclose($pipe);
        };
      }
      if (is_resource($this->process)) {
        $return = proc_close($this->process);
        $this->logger->info("Return status: @status ", ['@status' => $return]);
      }

      unset($this->process);
      unset($this->pipes);

      return $return;
    }
  }

  /**
   * Wait for a number of miliseconds.
   *
   * @param int $miliseconds
   */
  protected function wait($miliseconds = 100) {
    $this->logger->debug('Sleep miliseconds @ms', ['@ms' => $miliseconds]);
    usleep(1000 * $miliseconds);
  }

  /**
   * Check for timeout.
   *
   * @param $function
   *   Function name for logging.
   * @return boolean
   *   True if time < timeout.
   */
  protected function checkTimeout($function) {
    if (time() < $this->timeout) {
      return TRUE;
    }
    else {
      $this->logger->info('Timeout ' . $function);
      return FALSE;
    }
  }

  /**
   * Set timeout for command.
   *
   * @param $time
   *   Set new timeout if empty.
   * @return int
   *   Timestamp for timeout
   */
  protected function setTimeout($time = NULL) {
    return $this->timeout = $time ? $time : time() + $this->params['timeout'];
  }

  /**
   * Get printable logged messages.
   *
   * @return string
   */
  public function getLogs() {
    return $this->logger->formatLogs();
  }

  /**
   * Get errors.
   *
   * @return array|FALSE
   *   Array of strings with error messages if any.
   */
  public function getErrors() {
    if (isset($this->pipes) && is_resource($this->pipes[2])) {
      while ($error = fgets($this->pipes[2])) {
        $this->logger->error($error);
        $this->errors[] = $error;
      }
    }
    return isset($this->errors) ? $this->errors : FALSE;
  }

  /**
   * Magic destruct. No need for explicit closing.
   */
  public function __destruct() {
    $this->stop();
  }
}