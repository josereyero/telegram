<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramMessage
 *
 * Encapsulates telegram messages.
 */

namespace Drupal\telegram;

/**
 * Incoming / out going messages.
 * @author jose
 *
 */
class TelegramMessage extends TelegramData {

  /**
   * @var string
   */
  public $text;

  /**
   * @var Drupal/telegram/TelegramContact
   */
  public $from;
  public $to;

  /**
   * Set destination contact.
   */
  public function setDestination($contact) {
    $this->to = $contact;
    return $this;
  }

}