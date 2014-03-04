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
   * @param TelegramContact $contact.
   */
  function saveContact($contact) {
    return $this->getStorage()->contactSave($contact);
  }

  /**
   * Send message.
   *
   * @param DrupalTelegramMessage $message
   */
  function sendMessage($message) {
    $message->type = 'outgoing';
    $result = $this->getClient()->sendMessage($message->peer, $message->text);
    if ($result) {
      $message->sent = REQUEST_TIME;
      $message->status = $message::STATUS_DONE;
    }
    else {
      $message->status = $message::STATUS_ERROR;
    }
    $this->getStorage()->messageSave($message);
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
    // Add new contacts, remining in the array.
    foreach ($telegram as $contact) {
      $contact = new TelegramContact($contact);
      $this->getStorage()->contactSave($contact);
      $this->contacts[] = $contact;
    }
  }

  /**
   * Read new messages.
   *
   * @todo Get proper objects from TelegramClient.
   */
  function readNewMessages() {
    $list = $this->getClient()->getNewMessages();
    foreach ($list as $index => $message) {
      $message = new TelegramMessage($message);
      $message->type = 'incoming';
      $this->getStorage()->messageSave($message);
      $list[$index] = $message;
    }
    return $list;
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
    $this->getClient()->stop();
    unset($this->client);
    unset($this->storage);
  }
}
