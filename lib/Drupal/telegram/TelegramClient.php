<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramClient
 */

namespace Drupal\telegram;

use \streamWrapper;

class TelegramClient {

  // Regexps to parse response elements.
  const RX_USER = '[\w\s]+'; // Jose Reyero
  const RX_DATE = '\[[\w\s\:]\]'; // [20 Feb], [15:19]
  const RX_PENDING = '\d+\sunread'; // 0 unread

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
  protected $debug = 1;

  /**
   * Class constructor.
   */
  public function __construct(array $params) {
    // Add some defaults
    $params += array('debug' => 0);
    $this->params = $params;
    $this->debug = $params['debug'];
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
		    $patern = array(
		    0=>'/User\s\#(\d+)\:\s([\w\s]+)\s\((\w+)\s(\d+)\)\s(\offline)\.\s\w+\s\w+\s\[(\w+\/\w+\/\w+)\s(\w+\:\w+\:\w+)\]/u',
		    1=>'/User\s\#(\d+)\:\s([\w\s]+)\s\((\w+)\s(\d+)\)\s(\online)/',
		     );
		  
		    $key = array(
		    0 => 'string',
		    1 => 'id',
		    2 => 'namea',
		    3 => 'nameb',
		    4 => 'number',
		    5 => 'status',
		    6 => 'date',
		    7 => 'hour',);
    	    $response = $this->parseResponse($patern, $key);
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
  function getDialogList($filter) {
    if ($this->execCommand('dialog_list')) {
      // @todo Add the right regexp format for the response.
      
      if ($filter == 1)
        {
          $patern = array(
          0=> '/^User\s([\w\s]+)\:\s(\d+)\s(\w+)$/u',
          );
        }
      if ($filter == 2)
      {
      	 $patern = array(
      	 0 => '/^User\s([\w\s]+)\:\s(0)\s(\w+)$/u',
      	 );
      }
      if ($filter == 3)
      {
      	$patern = array(
      	0 => '/^User\s([\w\s]+)\:\s(1)\s(\w+)$/u',
      	);
      }
      $key = array(
      0 => 'string',
       1 => 'user',
       2 => 'messages',
       3 => 'state');
      return $this->parseResponse($patern, $key);
    }
  }

  /**
   * Add contact
   * Add contact can change a name contact
   */
  function AddContact($phone, $fname, $sname) {
  	$output = $this->execCommand('add_contact ' . $phone . ' ' .  $fname . ' ' . $sname);
  	 // @TODO test the exit of the command
  	return TRUE;
  }

  /**
   * Rename contact
   */
  function RenameContact($peer, $fname, $sname){
  	$output = $this->execCommand('rename_contact ' . $peer . ' ' . $fname . ' '. $sname);
  	return TRUE;
  }
  
 /**
  * Get history's peer 
  */
  function GetHistory($peer){
  	if ($this->execCommand('history '. $peer)) {
  	  $patern = array(
  	  0 => '/\[(\d+\s\w+)\]\s(\w+)\s(«««|»»»)\s(.*)/',
  	  );  	
  	  $key = array(
  	  0 => 'string',
  	  1 => 'date',
  	  2 => 'peer',
  	  3 => 'direction',
  	  4 => 'msg',);
  	  return $this->parseResponse($patern,$key);
  	}
  }
  
  /**
   * Mark as read messages of a peer 
   */
  function MarkAsRead($peer){
  	$output = $this->execCommand('mark_read ' . $peer );
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
   * @param $pattern
   *   Regexp with the response format.
   *
   * @return array|NULL
   *   Response array if any.
   */
  protected function parseResponse($pattern = NULL, $key) {
    if ($process = $this->getProcess()) {
    	if (!empty($key)){
          return $process->parseResponse($pattern, $key);
    	}
    	else {
    	  return $process->parseResponse($pattern);	
    	}
    }
  }

  /**
   * Start process.
   */
  function getProcess() {
    if (!isset($this->process)) {
      $this->start();
    }
    return $this->process;
  }

  /**
   * Start process.
   */
  function start() {
    $this->process = new TelegramProcess($this->params);
    $this->process->start();
    sleep(1);
  }

  /**
   * Exit process (send quit command).
   */
  function stop() {
    if (isset($this->process)) {
      $this->process->close();
      unset($this->process);
    }
  }

  /**
   * Log line in output.
   */
  function log($message) {
    //$this->output[] = $message;
    if ($this->debug) {
      print $message . "\n";
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
