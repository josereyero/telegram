<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramProcess
 */

namespace Drupal\telegram;

use \streamWrapper;

use \Exception;
use \ErrorException;

class TelegramProcess {

  // Running parameters.
  protected $params;

  // Running process
  protected $process;

  // Pipes for input / output streams
  protected $pipes;

  // Input / Output history.
  protected $lastCommand;
  protected $output;
  protected $input;
  protected $logs;
  protected $errors;
  protected $timeout;

  /**
   * @var TelegramLogger
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
  public function __construct(array $params, $logger) {
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
  function getCommandLine() {
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
   * @return FALSE|array
   *   Status array if process is open, FALSE if not.
   */
  function getStatus() {
    if (isset($this->process)) {
      return proc_get_status($this->process);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Low level exec function.
   */
  function execCommand($command, $params = '') {
    // Flush output
    $this->flush();
    // Build and sanitize parameters
    if ($params) {
      // @todo Better sanitize params.
      $params = $this->filter($params);
      $params = str_replace("\n", ' ', $params);
      $command .= ' ' . $params;
    }
    // Save last command executed.
    $this->lastCommand = $command;
    // Write command to the process input.
    $this->write($command . "\n");
    // Read until prompt
    $response = $this->readUntil('>');
    // Get command response.
    return $this->getResponse();
  }

  /**
   * Get response.
   *
   * Cycle until response is got, wait for prompt.
   */
  function getResponse() {
    // $this->output = NULL;
    $timeout = $this->setTimeout();
    while (!$this->output && $this->checkTimeout(__FUNCTION__)) {
      $response = $this->readUntil('>', $timeout);
      $this->output = $response;
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
  	$this->debug('parseResponse', $pattern);
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
		  for ($i=0 ; $i<$countresult ; $i++){
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
      $this->debug("parseResponse results", $result);
      // Returns resulting array from all matching lines
      return $result;
    }
    else {
      $this->debug("parseResponse empty");
      // No output, return empty array.
      return array();
    }

  }

  /**
   * Read multiple lines.
   */
  function readAll() {
    fflush($this->pipes[1]);
    $string = stream_get_contents($this->pipes[1]);
    $string = $this->filter($string);
    $this->debug('readAll', $string);
    return $string;
  }

  /**
   * Read single line.
   *
   * @param boolean $wait
   *   Whether to wait until it is available.
   */
  function readLine($wait = FALSE, $timeout = NULL) {
    $timeout = $this->setTimeout($timeout);

    $string = fgets($this->pipes[1]);

    while ($wait && $string === FALSE && $this->checkTimeout(__FUNCTION__)) {
      $string = fgets($this->pipes[1]);
      $this->wait();
    }

    if ($string !== FALSE) {
      $string = $this->filter($string);
      $this->log('readLine', $string);
    }

    return $string;
  }

  /**
   * Read until we find some (full line) string.
   *
   * @return array
   *   Array of (trimmed) string lines before the stop char.
   */
  function readUntil($stop = '>', $timeout = NULL) {
    $timeout = $this->setTimeout($timeout);
    $this->debug("readUntil $stop");
    $stop = trim($stop);
    $lines = array();
    $string = '';
    while ($string !== $stop && $this->checkTimeout(__FUNCTION__)) {
      if ($string) {
        $lines[] = $string;
      }
      $string = $this->readLine(TRUE, $timeout);
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
  function write($string, $flush = TRUE) {
    $this->debug("writeString: $string");
    $result = fwrite($this->pipes[0], $string);
    if ($flush) {
      fflush($this->pipes[0]);
    }
    return $result;
  }

  /**
   * Filter string, remove ANSI color codes.
   */
  function filter($string) {
    // Filter control codes in output
   	$string = preg_replace('/\x1B\[[0-9;]*[mK]/u', '', $string);
    return trim($string);
  }

  /**
   * Flush stream before issuing a command.
   */
  function flush() {
    $this->debug('flush');
    $this->readAll();
    // Get rid of output.
    $this->output = array();
  }

  /**
   * Start process.
   *
   * @return boolean
   *   Whether the program started successfully.
   */
  function start() {
    if (!isset($this->process)) {
      $this->pipes = array();
      $cwd = '/tmp';
      $descriptorspec = array(
         0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
         1 => array("pipe", "w+"),  // stdout is a pipe that the child will write to
         2 => array("pipe", "w+"),  // stderr
         //2 => array("file", '/tmp/telegram-error.txt', "a") // stderr is a file to write to
      );
      $this->process = proc_open($this->getCommandLine(), $descriptorspec, $this->pipes, $this->params['homepath']);

      if (is_resource($this->process)) {
        // Read first four lines that are credits and license messages.
        $this->readLine(TRUE);
        $this->readLine(TRUE);
        $this->readLine(TRUE);
        $this->readLine(TRUE);

        // Use non blocking streams.
        stream_set_blocking($this->pipes[1], 0);
        stream_set_blocking($this->pipes[2], 0);

        // Log initial status.
        $status = $this->getStatus();
        $pid = $status['pid'];
        $this->debug('Process status', $status);

        // Wait for a while. For some reason it needs some waiting before
        // we issue the first command.
        $this->wait(100);

        //$this->flush();
        // Read until prompt.
        $this->readUntil('>');

        if ($error = $this->getErrors()) {
          return FALSE;
        }
        else {
          $this->log("Process started with pid $pid");
        }
      }
      else {
        // Process failed, close everything.
        $this->logger->logError('Process failed to start, closing...');
        $this->getErrors();
        $this->close();
        return FALSE;
      }
    }
    return is_resource($this->process);
  }

  /**
   * Exit process (send quit command).
   */
  function close() {
    if (isset($this->process)) {
      $this->log('Closing process');
      if (is_resource($this->process)) {
        $this->write("quit\n");
        $this->wait(1000);
      }
      if (is_array($this->pipes)) {
        foreach ($this->pipes as $pipe) {
          fclose($pipe);
        };
      }
      if (is_resource($this->process)) {
        $return = proc_close($this->process);
        $this->log("Return status", $return);
      }

      unset($this->process);
      unset($this->pipes);

      return $return;
    }
  }

  /**
   * Shorthand for debug messages.
   */
  protected function debug($message, $args = NULL) {
    $this->logger->logDebug($message, $args);
  }

  /**
   * Shorthand for log messages.
   */
  protected function log($message, $args = NULL) {
    $this->logger->logInfo($message, $args);
  }

  /**
   * Wait for a number of miliseconds.
   *
   * @param int $miliseconds
   */
  function wait($miliseconds = 100) {
    $this->log('Sleep miliseconds', $miliseconds);
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
      $this->log('Timeout ' . $function);
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
  function setTimeout($time = NULL) {
    return $this->timeout = $time ? $time : time() + $this->params['timeout'];
  }

  /**
   * Get printable logged messages.
   *
   * @return string
   */
  function getLogs() {
    return $this->logger->formatLogs();
  }

  /**
   * Get errors.
   */
  function getErrors() {
    if (isset($this->pipes) && is_resource($this->pipes[2])) {
      while ($error = fgets($this->pipes[2])) {
        $this->logger->logError('getErrors', $error);
        $this->errors[] = $error;
      }
    }
    return isset($this->errors) ? $this->errors : NULL;
  }

  /**
   * Magic destruct. No need for explicit closing.
   */
  public function __destruct() {
    $this->close();
  }
}