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
  public $id;
  public $name;
  public $phone;
  public $peer;
  /**
   * Contact source:
   * - 'telegram', Showed up in telegram agenda.
   * - 'created', Added to contact list.
   * - 'message', Got a message from this number.
   *
   * @var string
   */
  public $source;
  public $uid = 0;
  public $verification;
  public $verified = 0;
  public $online = 0;

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

  /**
   * Get verification code (6 digits).
   */
  public function getVerificationCode($reset = FALSE) {
    if ($reset || !isset($this->verification)) {
      $this->verification = (string)mt_rand(100000, 999999);
    }
    return $this->verification;
  }

  /**
   * Get table name().
   */
  static function getDbTable() {
    return 'telegram_contact';
  }
}