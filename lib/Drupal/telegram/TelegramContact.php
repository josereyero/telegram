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
   * Unique Telegram contact id
   *
   * @var string
   */
  public $idcontact;

  /**
   * Other values from Telegram
   *
   * @var string
   */
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
  public $source = 'telegram';
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
    if (!empty($this->peer)) {
      return $this->peer;
    }
    elseif (!empty($this->name)) {
      return $this->nameToPeer($this->name);
    }
    else {
      return FALSE;
    }
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
   * Helper function. Convert contact name to peer name.
   */
  public static function nameToPeer($name) {
    return str_replace(' ', '_', $name);
  }

}
