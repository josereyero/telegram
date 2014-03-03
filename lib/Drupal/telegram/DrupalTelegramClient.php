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
   * Contacts indexed by phone number
   *
   * @var array
   */
  protected $contacts;

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
   * Create contact. Data must contain at least phone number.
   */
  protected function createContact(array $data) {
    $contact = new TelegramContact($data);
    $this->contacts[$contact->getPhone()] = $contact;
    return $contact;
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
    $message = new TelegramMessage(array('to' => $contact, 'text' => $text));
    if ($contact = $this->getContactByPhone()) {

      return $this->sendMessage($message);
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
   * @todo Lower level methods to be implemented property.
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
   * Add new contact.
   */
  public function addNewContact($phone, $first_name, $last_name) {
    $name = trim($first_name) . ' ' . trim($last_name);
    $peer = $this->nameToPeer($name);
    // @todo Actually create contact.
    $contact = $this->createContact(array('phone' => $phone, 'name' => name, 'peer' => $peer));
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
