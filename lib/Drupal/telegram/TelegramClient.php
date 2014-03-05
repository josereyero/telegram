<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramClient
 */

namespace Drupal\telegram;

use \streamWrapper;
use \Exception;

class TelegramClient {

  /**
   * Running parameters to pass to the process.
   *
   * @var array
   */
  protected $params;

  // Running process
  protected $process;


  protected $logs = array();

  // Debug level
  protected $logLevel = 0;
  protected $logFile;

  /**
   * Class constructor.
   */
  public function __construct(array $params) {
    // Add some defaults
    $params += array('debug' => 0);
    $this->params = $params;
    $this->logLevel = $params['debug'] ? 0 : 1;
    if (!empty($params['logfile'])) {
      if ($file = fopen($params['logfile'], 'a')) {
        $this->logFile = $file;
      }
      else {
        $this->logError('Error opening log file', $params['logfile']);
      }
    }
  }

  /**
   * Send message to phone number.
   */
  public function sendPhone($phone, $message) {
    $contacts = $this->getContactList();
    // @todo find peer name by contact.
    return $this->msg($peer, $message);
  }

  /**
   * Send message to peer.
   */
  public function sendMessage($peer, $message) {
    $output = $this->execCommand('msg', $peer . ' ' . $message);
    // @todo Parse output and get success / failure.
    return TRUE;
  }

  /**
   * Get contact list.
   *
   * @return array
   *   Contacts indexed by phone number.
   */
  function getContactList() {
    if (!isset($this->contacts)) {
		  if ($this->execCommand('contact_list')) {
		    $pattern = array(
		    0=>'/User\s\#(\d+)\:\s([\w\s]+)\s\((\w+)\s(\d+)\)\s(\offline)\.\s\w+\s\w+\s\[(\w+\/\w+\/\w+)\s(\w+\:\w+\:\w+)\]/u',
		    1=>'/User\s\#(\d+)\:\s([\w\s]+)\s\((\w+)\s(\d+)\)\s(\online)/',
		     );

		    $key = array(
		    0 => 'string',
		    1 => 'id',
		    2 => 'name',
		    3 => 'peer',
		    4 => 'phone',
		    5 => 'status',
		    6 => 'date',
		    7 => 'hour',);
    	    $response = $this->parseResponse($pattern, $key, 'phone');
    	    // @todo Parse response into a named array
    	    $this->contacts = $response;
		  }
    }
		return $this->contacts;
  }

  /**
   * Get list of current dialogs.
   * @params filter 1 for all, 2 for read, 3 for unread
   */
  function getDialogList($filter = 1) {
    if ($this->execCommand('dialog_list')) {
      // @todo Add the right regexp format for the response.

      if ($filter == 1)
        {
          $pattern = array(
          0=> '/^User\s([\w\s]+)\:\s(\d+)\s(\w+)$/u',
          );
        }
      if ($filter == 2)
      {
      	 $pattern = array(
      	 0 => '/^User\s([\w\s]+)\:\s(0)\s(\w+)$/u',
      	 );
      }
      if ($filter == 3)
      {
      	$pattern = array(
      	0 => '/^User\s([\w\s]+)\:\s(1)\s(\w+)$/u',
      	);
      }
      $key = array(
      0 => 'string',
       1 => 'user',
       2 => 'messages',
       3 => 'state');
      return $this->parseResponse($pattern, $key);
    }
  }

  /**
   * Add contact
   *
   * Add contact can change a name contact
   */
  function addContact($phone, $first_name, $last_name) {
  	$output = $this->execCommand('add_contact', $phone . ' ' .  $first_name . ' ' . $last_name);
  	 // @TODO test the exit of the command
  	return TRUE;
  }

  /**
   * Rename contact
   */
  function renameContact($peer, $first_name, $last_name){
  	$output = $this->execCommand('rename_contact', $peer . ' ' . $fname . ' '. $sname);
  	return TRUE;
  }

 /**
  * Get history's peer
  * @param $limit limit the results
  */
  function getHistory($peer, $limit = NULL){
  	if ($this->execCommand('history', $peer .' '.$limit)) {
  	  $pattern = array(
  	  0 => '/\[(\d+\s\w+)\]\s(\w+)\s(«««|»»»)\s(.*)/',
  	  );
  	  $key = array(
  	  0 => 'string',
  	  1 => 'date',
  	  2 => 'peer',
  	  3 => 'direction',
  	  4 => 'msg',);
  	  return $this->parseResponse($pattern,$key);
  	}
  }

  /**
   * Mark as read messages of a peer
   */
  function markAsRead($peer){
  	$output = $this->execCommand('mark_read', $peer );
  	return TRUE;
  }

  /**
   * Low level exec function.
   *
   * @param $command
   *   Command key
   * @param $args
   *   Command arguments.
   * @param $parse_response
   *   Optional regex to parse the response.
   *   None if we don't need a response.
   */
  protected function execCommand($command, $args = NULL) {
    // Make sure process is started.
    if ($process = $this->getProcess()) {
      return $process->execCommand($command, $args);
    }
  }

  /**
   * Parse process response.
   *
   * @param strwhatsapping|array $pattern
   *   Regexp with the response format.
   * @param array mapping
   *   Field mapping.
   * @param string index_field
   *
   * @return array
   *   Response array with objects indexed by index_field.
   */
  protected function parseResponse($pattern = NULL, $mapping = array(), $index_field = NULL) {
    $response = array();
    if (($process = $this->getProcess()) && ($list = $process->parseResponse($pattern, $mapping))) {
      foreach ($list as $key => $data) {
        $index = $index_field ? $data[$index_field] : $key;
        $response[$index] = (object)$data;
      }
    }
    return $response;
  }

  /**
   * Start process.
   */
  function getProcess() {
    $this->start();
    return $this->process;
  }

  /**
   * Start process.
   */
  function start() {
    if (!isset($this->process)) {
      $process = new TelegramProcess($this->params, $this);
      if ($process->start()) {
        // Process started OK
        $this->process = $process;
        $this->logInfo('Process started from client');
      }
      else {
        // Process start failed, set to FALSE so we don't try to create it again.
        $this->logError('Failed process start');
        $this->process = FALSE;
        throw new Exception('Telegram process failed to start');
      }
    }
  }

  /**
   * Exit process (send quit command).
   */
  function stop() {
    if (isset($this->process)) {
      $this->logInfo('Client stopping process');
      $this->process->close();
      unset($this->process);
    }
    if (isset($this->logFile)) {
      fclose($this->logFile);
      unset($this->logFile);
    }
  }


  /**
   * Log line in output.
   */
  function logInfo($message, $args = NULL) {
    $this->log($message, $args, 1);
  }

  /**
   * Log debug message.
   */
  function logDebug($message, $args = NULL) {
    $this->log($message, $args, 0);
  }


  /**
   * Log error message.
   */
  function logError($message, $args = NULL) {
    $this->log($message, $args, 5);
  }

  /**
   * Log debug message if in debug mode.
   */
  function debug($message, $args = NULL) {
    $this->logDebug($message, $args);
  }

  /**
   * Log message / mixed data.
   *
   * @param mixed $message
   */
  function log($message, $args, $severity) {
    if ($severity >= $this->logLevel) {
      $txt = is_string($message) ? $message : print_r($message, TRUE);
      if ($args) {
        $txt .= ': ';
        $txt .= is_string($args) ? $args : print_r($args, TRUE);
      }
      $this->logs[] = $txt;
      // Write to log file.
      if (isset($this->logFile)) {
        fwrite($this->logFile, $txt . "\n");
      }
      // Write to error log.
      if ($severity >= 5) {
        error_log($txt);
      }
    }
  }

  /**
   * Get logged messages.
   */
  function getLogs() {
    return isset($this->logs) ? $this->logs : array();
  }

}
