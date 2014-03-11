<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramInterface
 */

namespace Drupal\telegram;

/**
 * Drupal Telegram Manager
 *
 * Interface to telegram client.
 */
interface TelegramInterface {

  /**
   * Start process and connection.
   *
   * @return boolean
   *   TRUE if process started successfully.
   */
  public function start();

  /**
   * Exit process and connection
   *
   * @return boolean
   *   TRUE if the process stopped normally.
   */
  public function stop();

  /**
   * Send message to peer.
   *
  * @param string $peer
  *   Peer name or contact name with spaces replaced by '_'
   * @param string $text
   *   Message text
   *
   * @return TelegramMessage|FALSE
   */
  public function sendMessage($peer, $text);

  /**
   * Get contact list.
   *
   * @return array
   *   Contact objects indexed by phone number.
   */
  public function getContactList();

  /**
   * Get list of current dialogs.
   *
   * @todo Remove filters, just get dialog list
   */
  public function getDialogList();

  /**
   * Add contact
   *
   * Add contact can change a name contact
   *
   * @param string $phone
   *   Phone number, with country code but without '+' nor '00'
   * @param string $first_name
   *   Contact's first name, a single word.
   * @param string $first_name
   *   Contact's last name, a single word.
   *
   * @return TelegramContact|NULL
   */
  public function addContact($phone, $first_name, $last_name);

  /**
   * Rename contact
   *
   * @param string $peer
   *   Peer name or contact name with spaces replaced by '_'
   *
   * @param string $first_name
   *   Contact's first name, a single word.
   * @param string $lat_name
   *   Contact's last name, a single word.
   *
   * @return TelegramContact|NULL
   */
  public function renameContact($peer, $first_name, $last_name);

 /**
  * Get peer's message history
  *
  * @param string $peer
  *   Peer name or contact name with spaces replaced by '_'
  * @param int $limit
  *   Maximum number of messages, defaults to 40
  *
  * @return array
  *   Array of message objects
  */
  public function getHistory($peer, $limit = 40);

  /**
   * Mark as read messages of a peer
   *
   * @param string $peer
   *   Peer name or contact name with spaces replaced by '_'
   */
  public function markAsRead($peer);
}