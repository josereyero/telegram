<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramClient
 */

namespace Drupal\telegram;

use \streamWrapper;
use \Exception;

/**
 * Telegram Client
 */
class TelegramClient {

  /**
   * Running parameters to pass to the process.
   *
   * @var array
   */
  protected $params;

  /**
   * Process wrapper
   *
   * @var TelegramProcess
   */
  protected $process;

  /**
   * @var TelegramLogger
   */
  protected $logger;

  /**
   * Class constructor.
   *
   * @var TelegramProcess $process
   *   Mixed params
   * @var TelegramLogger $logger
   *   Logging interface.
   */
  public function __construct($process, $logger) {
    $this->process = $process;
    $this->logger = $logger;
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

  	  return $this->parseResponse($pattern, $key, 'phone');
	  }
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
  	  0 => '/(\d+)\s\[(.*.)\]\s+(.*.)\s(«««|»»»|<<<|>>>)(.*)/u',
  	  );

  	  $key = array(
  	  0 => 'string',
  	  1 => 'idmsg',
  	  2 => 'date',
  	  3 => 'peer',
  	  4 => 'direction',
  	  5 => 'msg',);
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
   * Get process, make sure it it started.
   *
   * @return TelegramProcess
   *   Started telegram process.
   */
  function getProcess() {
    if ($this->start()) {
      return $this->process;
    }
  }

  /**
   * Get logger.
   *
   * @return TelegramLogger
   */
  function getLogger() {
    return $this->logger;
  }

  /**
   * Start process.
   */
  function start() {
    if (isset($this->process)) {
      return $this->process->start();
    }
  }

  /**
   * Exit process (send quit command).
   */
  function stop() {
    if (isset($this->process)) {
      $this->log('Client stopping process');
      $this->process->close();
      unset($this->process);
    }
  }

  /**
   * Shorthand for debug message.
   */
  protected function debug($message, $args = NULL) {
    $this->logger->logDebug($message, $args);
  }

  /**
   * Shorthand for log message.
   *
   * @param mixed $message
   */
  protected function log($message, $args = NULL) {
    $this->logger->logInfo($message, $args);
  }

  /**
   * Get logged messages.
   */
  function getLogs() {
    return $this->logger->formatLogs();
  }

  /**
   * Class destructor.
   */
  public function __destruct() {
    $this->stop();
    unset($this->logger);
  }

  /**
   * Helper function. Convert contact name to peer name.
   */
  public static function nameToPeer($name) {
    return str_replace(' ', '_', $name);
  }
}
