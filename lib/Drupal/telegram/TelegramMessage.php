<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramMessage
 *
 * Encapsulates telegram messages.
 */

namespace Drupal\telegram;

/**
 * Incoming / outgoing messages.
 */
class TelegramMessage extends TelegramData {

  /**
   * Message status.
   */
  const STATUS_DONE = 0;
  const STATUS_PENDING = 1;
  const STATUS_ERROR = 2;

  /**
   * Telegram message id.
   *
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $phone;
  public $source;
  public $peer;
  public $text;

  /**
   * Message status (incoming, outgoing).
   *
   * @var string
   */
  public $type = 'incoming';

  /**
   * Possible values are 0 = done, 1 = queued, 2 = error
   */
  public $status = 0;

  /**
   * User id.
   */
  public $uid;

  /**
   * @var Drupal/telegram/TelegramContact
   */
  protected $contact;

  /**
   * Set destination contact.
   */
  public function setDestination($contact) {
    $this->type = 'outgoing';
    return $this->setContact($contact);
  }

  /**
   * Set contact data to message.
   */
  public function setContact($contact) {
    foreach (array('phone', 'peer', 'uid') as $field) {
      if (isset($contact->$field)) {
        $this->$field = $contact->$field;
      }
    }
    return $this;
  }

}
