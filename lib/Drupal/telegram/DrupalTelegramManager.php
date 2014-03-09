<?php

/**
 * @file
 * Definition of Drupal/telegram/DrupalTelegram
 */

namespace Drupal\telegram;

/**
 * Drupal Telegram Manager
 *
 * Manages all Telegram objects.
 */
class DrupalTelegramManager {
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
   * Get contact for user account.
   */
  function getUserContact($account) {
    $contacts = $this->getStorage()->contactLoadMultiple(array('uid' => $account->uid));
    return reset($contacts);
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
   * Create contact from user account.
   *
   * @todo Create better names from user accounts.
   */
  function createUserContact($account, $phone) {
    // Check for existing contact first.
    $contact = $this->getContactByPhone($phone);

    if (!$contact) {
      $first_name = 'Drupal';
      $last_name = 'User' . $account->uid;
      $contact = new TelegramContact(array(
        'uid' => $account->uid,
        'source' => 'drupal',
        'phone' => $phone,
        'name' => $first_name . ' ' . $last_name,
        'peer' => $first_name . '_' . $last_name,
      ));
      // Add telegram contact if newly created.
      $result = $this->getClient()->addContact($phone, $first_name, $last_name);
    }
    $contact->uid = $account->uid;
    $contact->verified = 0;
    $code = $contact->getVerificationCode(TRUE);

    //$contact->status = $result ? TelegramContact::STATUS_DONE : TelegramContact::STATUS_ERROR;

    $this->saveContact($contact);

    return $contact;
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
   * Create and send verification code.
   */
  function sendVerification($contact, $recreate = FALSE) {
    $code = $contact->getVerificationCode($recreate);
    if ($recreate) {
      $this->saveContact($contact);
    }
    $text = t('Your @site-name verification code is: @code', array(
      '@site-name' => variable_get('site_name'),
      '@code' => $code,
    ));
    $message = new TelegramMessage(array('text' => $text));
    $message->setContact($contact);
    return $this->sendMessage($message);
  }

  /**
   * Check verification code.
   */
  function verifyContact($contact, $code) {
    if ($contact->getVerificationCode() === $code) {
      $contact->verified = 1;
      $this->saveContact($contact);
      return TRUE;
    }
    else {
      return FALSE;
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
   * Send message.
   *
   * @param DrupalTelegramMessage $message
   */
  function sendMessage($peer, $text) {
    if ($contact = $this->getContactByName($peer)) {
      return $this->sendToContact($contact, $text);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Send message to contact.
   */
  function sendToContact($contact, $text) {
    $result = $this->getClient()->sendMessage($contact->peer, $text);
    $message = new TelegramMessage(array(
      'text' => $text,
      'type' => 'outgoing',
    ));
    $message->setDestination($contact);
    if ($result) {
      $message->sent = REQUEST_TIME;
      $message->status = $message::STATUS_DONE;
    }
    else {
      $message->status = $message::STATUS_ERROR;
    }
    $this->getStorage()->messageSave($message);
    return $message;
  }


  /**
   * Get contact list.
   */
  function getContactList($conditions = array()) {
    return $this->getStorage()->contactLoadMultiple($conditions);
  }

  /**
   * Refresh contact list.
   *
   * Compare stored contact list with the current Telegram list,
   * update status for existing contacts, add new ones.
   */
  function refreshContactList() {
    $stored = $this->getContactList();
    $telegram = $this->getClient()->getContactList();

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

  /**
   * Get message list.
   */
  function getMessageList($conditions = array()) {
    return $this->getStorage()->messageLoadMultiple($conditions);
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
      $stored = $this->getStorage()->messageLoadMultiple(array('id' => $message->id));
      if ($existing = reset($stored)) {
        // @todo update stored one?
        $result['updated'][$existing->id] = $existing;
      }
      else {
        $message->source = 'telegram';
        $this->saveMessage($message);
        $result['created'][$message->id] = $message;
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
    $dialog_list = $this->getClient()->getDialogList();
    $count = 0;
    $messages = array();
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
    return $messages;
  }

  /**
   * Read new messages.
   *
   * @todo Get proper objects from TelegramClient.
   */
  function readNewMessages() {
    $list = $this->getNewMessages();
    foreach ($list as $index => $message) {
      $message = new TelegramMessage($message);
      $message->type = 'incoming';
      $this->getStorage()->messageSave($message);
      $list[$index] = $message;
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
   * Magic destruct. No need for explicit closing.
   */
  public function __destruct() {
    $this->getClient()->stop();
    unset($this->client);
    unset($this->storage);
  }
}
