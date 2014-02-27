<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramClient
 */

namespace Drupal\telegram;

class TelegramClient {

  // Running parameters.
  protected $commandLine;

  // Running process
  protected $process;
  // Pipes for input / output streams
  protected $pipes;
  // Output history.
  protected $output = array();

  // Contact list array.
  /**
   * Class constructor.
   */
  public function __construct($command = '/usr/local/bin/telegram', $keyfile = '/etc/telegram/server.pub') {
    $this->commandLine = $command . ' -k ' . $keyfile;
  }

  /**
   * Get contact list.
   *
   * @return array
   *   Contacts indexed by phone number.
   */
  function getContactList() {
    if (!isset($this->contacts)) {
      $this->contacts = array();
      $output = $this->execCommand('contact_list');
      // Multiple lines of the form:
      // User #12345678: User Name (User_Name 341233444)....
      // print $output;
      foreach (explode("\n", $output) as $line) {
        $line = trim($line);
        $this->contacts[] = $line;
      }
    }
    return $this->contacts;
  }

  /**
   * Get raw output form command history.
   */
  function getRawOutput() {
    return $this->output;
  }
  /**
   * Low level exec function.
   */
  function execCommand($command) {
    // Make sure process is started.
    $this->startProcess();
    // Flush output ?
    // Run command.
    $this->writeString($command);
    // Give it some time, 1 sec.
    sleep(1);
    $string = $this->readString();
    // Filter control codes in output
    //$string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $string);
    return $string;
  }

  /**
   * Low level write (adds line end).
   */
  function writeString($string) {
    fwrite($this->pipes[0], $string . "\n");
  }

  /**
   * Low level read.
   */
  function readString() {
    $string = '';
    while(!isset($info) || $info['unread_bytes']) {
      $string .= fgetc($this->pipes[1]);
      $info = stream_get_meta_data($this->pipes[1]);
    }
    $this->output[] = $string;
    return $string;
  }

  /**
   * Start process.
   */
  function startProcess() {
    if (!isset($this->pipes)) {
      $this->pipes = array();
      $cwd = '/tmp';
      $descriptorspec = array(
         0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
         1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
         2 => array("file", '/tmp/telegram-error.txt', "a") // stderr is a file to write to
      );
      $this->process = proc_open($this->commandLine, $descriptorspec, $this->pipes, $cwd);
      // Flush initial message.
      $this->readString();
    }
    return is_resource($this->process);
  }

  /**
   * Exit process (send quit command).
   */
  function exitProcess() {
    if (isset($this->process)) {
      $this->writeString('quit');
      fclose($this->pipes[1]);
      fclose($this->pipes[0]);
      $return = proc_close($this->process);
      unset($this->process);
      return $return;
    }
  }

  /**
   * 
   * Parser for contact_list lines
   * return @array
   */
  function ParseContactList($cadena)
    {
	  $replace = array('(', ')', '[', ']', ':', '"', '#','.');        
	  $idinit = strpos($cadena, '#')+1;
	  $idend = strpos($cadena, ':');
	  $cnameend = strpos($cadena, '(');
	  $cnameoend = strpos($cadena, ')');
	  $cnameocon = str_replace($replace, '', substr($cadena, $cnameend, $cnameoend));
	  $statusinit = strpos($cadena, ')');
	  $statusend = strpos($cadena, '.');
	  $lastcondinit = strpos($cadena, '[');
	  $lastconhend = strpos($cadena, ']');
	  $linea['usid'] = substr($cadena, $idinit, $idend-$idinit);        
	  $linea['cname'] =  substr($cadena, $idend+2, $cnameend-$idend-2);
	  sscanf ($cnameocon, '%s %s', $linea['cnameo'], $linea['number'] );
	  $linea['lastcond'] = substr($cadena, $lastcondinit+1, 10);
	  $linea['lastconh'] = substr($cadena, $lastcondinit+11, 9);
	  $this->contacts[] = $linea;
	  return $this->$linea;
  }

}
