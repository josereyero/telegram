<?php

/**
 * @file
 * Definition of Drupal/telegram/DrupalTelegram
 */

namespace Drupal\telegram;

/**
 * Drupal Telegram Client
 *
 * This is a wrapper around TelegramClient using some Drupal specific features.
 *
 * It uses a locking mechanism to prevent starting more than one process.
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
   * Control start and lock status.
   */
  protected $lock;

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
   * Overrides TelegramClient::start()
   */
  function start() {
    if (!isset($this->lock)) {
      if (lock_acquire('telegram_client', 10.0)) {
        $this->lock = TRUE;
      }
      else {
        $this->logger->logError('Failed to acquire Drupal lock on the process');
        return FALSE;
      }
    }
    return parent::start();
  }

  /**
   * Overrides TelegramClient::stop()
   */
  function stop() {
    if (isset($this->lock)) {
      lock_release('telegram_client');
      unset($this->lock);
    }
    return parent::stop();
  }
}
