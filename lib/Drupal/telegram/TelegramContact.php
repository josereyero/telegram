<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramContact
 *
 * Encapsulates telegram messages.
 */

namespace Drupal\telegram;

class TelegramContact extends TelegramData {

  /**
   * @var string
   */
  public $name;
  public $phone;
  public $peer;

  /**
   * Get phone number.
   */
  public function getPhone() {
    return $this->phone;
  }

  /**
   * Get peer name.
   */
  public function getPeer() {
    return $this->peer;
  }
}