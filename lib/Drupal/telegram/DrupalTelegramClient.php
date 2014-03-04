<?php

/**
 * @file
 * Definition of Drupal/telegram/DrupalTelegram
 */

namespace Drupal\telegram;

/**
 * Drupal Telegram Client
 *
 * Runs commands through the client and parses responses.
 */
class DrupalTelegramClient extends TelegramClient {

  /**
   * Inbox, outbox.
   *
   * @var array
   */
  protected $inbox;
  protected $outbox;

  /**
   * Create and populate with test data.
   *
   * @todo Remove
   */
  public function __construct(array $params) {
    parent::__construct($params);
  }

  /**
   * Get peer name with phone number.
   *
   * @return Drupal/telegram/TelegramContact
   */
  public function getContactByPhone($phone) {
    $contacts = $this->getContactList();
    return isset($contacts[$phone]) ? $contacts[$phone] : NULL;
  }

  /**
   * Send message to phone number.
   *
   * @param string $phone
   *   Phone number without '+' or '00'. Example 34123123123
   *
   * @return array|FALSE
   */
  public function sendToPhone($phone, $text) {
    if ($contact = $this->getContactByPhone($phone)) {
      return $this->sendMessage($contact->peer, $text);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Read all pending incoming messages.
   */
  public function getAllMessages() {
    $this->readMessages();
    return $this->inbox;
  }

  /**
   * @todo Lower level methods to be implemented properly.
   */

  /**
   * Send message to peer.
   *
   * @param string $peer
   *   Peer name, like 'Jose_Reyero'
   *
   * @return array|FALSE
   */
  public function sendToPeer($peer, $message) {
    return $this->sendMessage($peer, $message);
  }

  /**
   * Read array of messages.
   * @return multitype:string
   */
  public function readMessages() {
    if (!isset($this->inbox)) {
      $this->inbox = array();
    }
  }

  /**
   * Helper function.
   */
  public static function nameToPeer($name) {
    return str_replace(' ', '_', $name);
  }

  /**
   * Magic destruct. No need for explicit closing.
   */
  public function __destruct() {
    $this->stop();
  }
}
