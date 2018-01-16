<?php

/**
 * @file
 * Definition of Drupal/telegram/DrupalTelegram
 */

namespace Drupal\telegram_cli;

use Drupal\telegram\TelegramInterface;
use Drupal\telegram\TelegramContact;

/**
 * Drupal Telegram Client
 *
 * This is a wrapper around TelegramClient using some Drupal specific features.
 *
 * It uses a locking mechanism to prevent starting more than one process.
 */
class DrupalTelegramClient extends TelegramClient implements TelegramInterface {

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
   * Implements TelegramInterface::getContactByPhone()
   */
  public function getContactByPhone($phone) {
    $contacts = $this->getContactList();
    return isset($contacts[$phone]) ? $contacts[$phone] : NULL;
  }

  /**
   * Implements TelegramInterface::getContactByName()
   */
  function getContactByName($name) {
    $peer = TelegramContact::nameToPeer($name);
    return $this->getContactInfo($peer);
  }

  /**
   * Read all pending incoming messages.
   */
  public function getAllMessages() {
    $this->readMessages();
    return $this->inbox;
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
    // Try to acquire lock on the process.
    // If not available, wait for 10 secs and retry.
    if (!isset($this->lock)) {
      if (lock_acquire('telegram_client', 15.0)) {
        $this->lock = TRUE;
      }
      else {
        $this->logger->logInfo('Drupal. Waiting for lock to be available (15 seconds)');
        $this->lock = lock_wait('telegram_client', 15);
      }
    }
    if ($this->lock) {
      return parent::start();
    }
    else {
      $this->logger->logError('Drupal. Failed to acquire lock on the process');
      return FALSE;
    }
  }

  /**
   * Overrides TelegramClient::stop()
   */
  function stop() {
    if (!empty($this->lock)) {
      lock_release('telegram_client');
      unset($this->lock);
    }
    return parent::stop();
  }
}
