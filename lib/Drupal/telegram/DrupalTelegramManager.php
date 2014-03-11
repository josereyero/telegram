<?php

/**
 * @file
 * Definition of Drupal/telegram/DrupalTelegram
 */

namespace Drupal\telegram;

use \DrupalQueue;

/**
 * Drupal Telegram Manager
 *
 * Manages all Telegram objects.
 */
class DrupalTelegramManager implements TelegramInterface {
  /**
   * @var DrupalTelegramClient
   */
  protected $client;

  /**
   * @var DrupalTelegramStorage
   */
  protected $storage;

  /**
   * @var DrupalTelegramStorage
   */
  protected $contacts;

  /**
   * Constructor.
   */
  public function __construct($client, $storage) {
    $this->client = $client;
    $this->storage = $storage;
  }

  /**
   * Get telegram client.
   */
  function getClient() {
    return $this->client;
  }

  /**
   * Get storage
   */
  function getStorage() {
    return $this->storage;
  }

  /**
   * Get contact for phone.
   */
  function getContactByPhone($phone) {
    $contacts = $this->getStorage()->contactLoadMultiple(array('phone' => $phone));
    return reset($contacts);
  }

  /**
   * Get contact by name / peer.
   */
  function getContactByName($name) {
    $peer = TelegramContact::nameToPeer($name);
    $contacts = $this->getStorage()->contactLoadMultiple(array('peer' => $peer));
    return reset($contacts);
  }

  /**
   * Remove contact from user account.
   */
  function removeUserContact($account) {
    if ($contact = $this->getUserContact($account)) {
      $this->getStorage()->contactDelete($contact);
      // @todo Remove contact from Telegram list
    }
  }

  /**
   * Create contact (queue job).
   *
   * @param array $data
   *   Contact data that must contain at least 'phone', 'first_name', 'last_name'
   */
  function contactCreate($data) {
    $contact = new TelegramContact($data);
    $this->saveContact($contact);
    $this->queueJob('contact create', $contact, array($data['phone'], $data['first_name'], $data['last_name']));
    return $contact;
  }

  /**
   * Update contact (queue job).
   *
   * @param TelegramContact $contact
   *
   * @param array $data
   *   Contact data that must contain at least 'first_name', 'last_name'
   */
  function contactUpdate($contact, $data) {
    if (isset($data['first_name']) && isset($data['last_name'])) {
      $peer = $contact->getPeer();
      $contact->setFullName($data['first_name'], $data['last_name']);

    }
  }

  /**
   * Save contact to storage.
   *
   * @param TelegramContact $contact.
   */
  function saveContact($contact) {
    return $this->getStorage()->contactSave($contact);
  }

  /**
   * Save message to storage.
   *
   * We may need to fix the text encoding to avoid this type of errors:
   *
   * PDOException: SQLSTATE[HY000]: General error: 1366
   * Incorrect string value: '\xF0\x9F\x98\x9C' for column 'text' at row 1:
   *
   * (This happens with a smiley).
   */
  function saveMessage($message) {
    // For new messages, sanitize text body.
    if (!isset($message->oid) && $message->source == 'telegram') {
      /*
      if (!mb_check_encoding($message->text, 'UTF-8')) {
        $message->text = utf8_encode($message->text);
        dpm($message->text, 'fixed encoding, method 1');
      }
      */
      // Detected encoding is UTF-8 for these ones
      //$encoding = mb_detect_encoding($message->text);
      //dpm($message->text, "Encoding: $encoding");
      $message->text = utf8_encode($message->text);
    }
    return $this->getStorage()->messageSave($message);
  }

  /**
   * Send message (immediate sending).
   *
   * @param DrupalTelegramMessage $message
   */
  function postMessage($message) {
    $result = $this->getClient()->sendMessage($message->peer, $message->text);
    // @todo Handle return and error conditions
    if ($result) {
      $message->sent = REQUEST_TIME;
      $message->status = TelegramMessage::STATUS_DONE;
      $this->saveMessage($message);
      watchdog('telegram', 'Telegram message @number has been sent', array('@number' => $message->oid));
    }
    else {
      $message->status = TelegramMessage::STATUS_ERROR;
      $this->saveMessage($message);
      watchdog('telegram', 'Telegram message @number cannot be sent', array('@number' => $message->oid), WATCHDOG_ERROR);
    }
    return $result;
  }

  /**
   * Queue message for delivery.
   */
  function queueMessage($message) {
   $message->direction = 'outgoing';
   $message->source = 'drupal';
   $message->status = TelegramMessage::STATUS_PENDING;
   $this->saveMessage($message);
   $this->queueJob('message send', $message);
   return $message;
  }

  /**
   * Queue work to do on next connection.
   */
  function queueJob($op, $object, $params = array()) {
    $queue = DrupalQueue::get('telegram_manager');
    return $queue->createItem((object)array(
      'op' => $op,
      'object' => $object,
      'params' => $params,
    ));
  }

  /**
   * Get contact list.
   */
  function getContacts($conditions = array(), $options = array()) {
    return $this->getStorage()->contactLoadMultiple($conditions, $options);
  }

  /**
   * Get message list.
   */
  function getMessages($conditions = array(), $options = array()) {
    return $this->getStorage()->messageLoadMultiple($conditions, $options);
  }

  /**
   * Refresh contact list.
   *
   * Compare stored contact list with the current Telegram list,
   * update status for existing contacts, add new ones.
   */
  function refreshContactList() {
    if ($telegram = $this->getClient()->getContactList()) {
      $stored = $this->getContactList();

      foreach ($stored as $index => $contact) {
        if (isset($telegram[$contact->phone])) {
          // Existing contact, update values
          $contact->setData($telegram[$contact->phone]);
          $this->saveContact($contact);
          // Remove from array
          unset($telegram[$contact->phone]);
        }
        else {
          // Contact, deleted. Delete?
          // @todo Decide later.
        }
      }
      // Add new contacts, remaining in the array.
      foreach ($telegram as $contact) {
        $this->getStorage()->contactSave($contact);
        $this->contacts[] = $contact;
      }
    }
  }

  /**
   * Refresh stored messages.
   *
   * @param int $limit
   *   Maximum number of messages to process.
   * @param boolean $new
   *   Whether to read only new messages.
   *
   * @return array
   *   Multiple lists of messages indexed by state.
   */
  function refreshMessages($limit = 0, $new = TRUE) {
    $list = $this->readMessages($limit, $new);
    $result = array();
    foreach ($list as $message) {
      $stored = $this->getStorage()->messageLoadMultiple(array('idmsg' => $message->idmsg));
      if ($existing = reset($stored)) {
        // @todo update stored one?
        $result['updated'][$existing->idmsg] = $existing;
      }
      else {
        $message->source = 'telegram';
        $this->saveMessage($message);
        $result['created'][$message->idmsg] = $message;
      }
    }
    return $result;
  }

  /**
   * Get new messages.
   *
   * @todo When getting user history, the newest messages may not be the incoming ones.
   *
   * @param int $limit
   *   Limit the number of messages.
   * @param booloean $new
   *   TRUE to get only new messages.
   */
  protected function readMessages($limit = 0, $new = TRUE) {
    $messages = array();

    if ($dialog_list = $this->getClient()->getDialogList()) {
      $count = 0;

      while ((!$limit || $count < $limit) && $dialog = array_shift($dialog_list)) {
        if (!$new || $dialog->messages && $dialog->state == 'unread') {
          $read = $new ? (int)$dialog->messages : 40;
          $read = $limit ? min($read, $limit - $count) : $read;
          $peer = TelegramContact::nameToPeer($dialog->user);
          if ($more = $this->getClient()->getHistory($peer, $read)) {
            $messages = array_merge($messages, $more);
            $count += count($more);
          }
        }
      }
    }
    return $messages;
  }

  /**
   * Read new messages.
   *
   * @todo Get proper objects from TelegramClient.
   */
  function readNewMessages($limit = 0) {
    $list = $this->readMessages($limit);
    foreach ($list as $index => $message) {
      $message->type = 'incoming';
      $this-saveMessage($message);
    }
    return $list;
  }

  /**
   * Delete messages.
   */
  function deleteMessages($conditions = array()) {
    return $this->getStorage()->messageDeleteAll($conditions);
  }

  /**
   * Process queued work.
   */
  public function processJob($item) {
    dpm($item);
    if ($this->getClient()->start()) {
      $params = $item->params;
      switch ($item->op) {
        case 'message send':
          return $this->postMessage($item->object);
        case 'contact create':
          $result = $this->getClient()->addContact($params['phone'], $params['first_name'], $params['last_name']);
          break;
        case 'contact rename':
          $result = $this->getClient()->renameContact($params['peer'], $params['first_name'], $params['last_name']);
          break;
      }
    }
    else {
      watchdog('telegram', 'Cannot start telegram client', array(), WATCHDOG_ERROR);
      return FALSE;
    }
  }

  /**
   * Implements DrupalTelegramInterface::start()
   */
  public function start() {
    return TRUE;
  }

  /**
   * Implements DrupalTelegramInterface::stop()
   */
  public function stop() {
    return TRUE;
  }

  /**
   * Implements DrupalTelegramInterface::getContactList()
   */
  public function sendMessage($peer, $text) {
    if ($contact = $this->getContactByName($peer)) {
      $message = new TelegramMessage(array(
        'source' => 'drupal',
        'text' => $text,
        'direction' => 'outgoing',
      ));
      $message->setDestination($contact);
      return $this->queueMessage($message);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements DrupalTelegramInterface::getContactList()
   */
  function getContactList() {
    return $this->getContacts();
  }

  /**
   * Implements DrupalTelegramInterface::getContactList()
   */
  public function getDialogList() {
    // @todo How to implement this ??
    return array();
  }

  /**
   * Implements DrupalTelegramInterface::addContact()
   */
  public function addContact($phone, $first_name, $last_name) {
    return contactCreate(array(
      'phone' => $phone,
      'first_name' => $first_name,
      'last_name' => $last_name,
    ));
  }

  /**
   * Implements DrupalTelegramInterface::renameContact()
   */
  public function renameContact($peer, $first_name, $last_name) {
    if ($contact = $this->getContactByName($peer)) {
      $name = trim($first_name) . '_' . trim($last_name);
      return contactUpdate($contact, array(
        'peer' => $peer,
        'first_name' => $first_name,
        'last_name' => $last_name,
      ));
    }
  }

  /**
   * Implements DrupalTelegramInterface::getHistory()
   */
  public function getHistory($peer, $limit = 40) {
    return $this->getMessages(array('peer' => $peer), array(
      'order' => array('created' => 'DESC'),
      'limit' => $limit,
    ));
  }

  /**
   * Implements DrupalTelegramInterface::markAsRead()
   */
  public function markAsRead($peer) {
    return $this->getStorage()->messageUpdate(array('peer' => $peer), array('readtime' => REQUEST_TIME));
  }



  /**
   * Magic destruct. No need for explicit closing.
   */
  public function __destruct() {
    $this->getClient()->stop();
    unset($this->client);
    unset($this->storage);
  }

}
