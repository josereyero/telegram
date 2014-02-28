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

  // Running parameters.
  protected $command;
  protected $keyfile;

  // Running process
  protected $process;


  protected $logs = array();

  // Debug level
  protected $debug = 1;

  /**
   * Class constructor.
   */
  public function __construct($command = '/usr/local/bin/telegram', $keyfile = '/etc/telegram/server.pub') {
    $this->command = $command;
    $this->keyfile = $keyfile;
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
    $output = $this->execCommand('msg ' . $peer . ' ' . $message);
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
      $this->contacts = array();
      $output = $this->execCommand('contact_list');
      // Multiple lines of the form:
      // User #12345678: User Name (User_Name 341233444)....
      foreach (explode("\n", $output) as $line) {
        $line = trim($line);
        $this->contacts[] = $line;
      }
    }
    return $this->contacts;
  }

  /**
   * Get list of current dialogs.
   */
  function getDialogList() {
    if ($process->execCommand('dialog_list')) {
      $process->parseResponse();
    }
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
  function execCommand($command, $args = NULL) {
    // Make sure process is started.
    if ($process = $this->getProcess()) {
      return $process->execCommand($command, $args);
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
    $this->process = new TelegramProcess($this->command, $this->keyfile, $this->debug);
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

}