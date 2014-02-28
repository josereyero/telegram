<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramCommand
 */

namespace Drupal\telegram;

/**
 * Telegram command interpreter.
 *
 * Runs commands through the client and parses responses.
 *
 * @author jose
 *
 */
class TelegramCommand {

  /**
   * @var TelegramProcess
   */
  protected $process;

  public function __construct($process) {
    $this->process = $process;
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

  function getContactByName($name) {

  }

  function getContactByPhone($phone) {

  }
}