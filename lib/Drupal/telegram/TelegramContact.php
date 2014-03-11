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
   * Temporary values, only for creation.
   */
  public $first_name;
  public $last_name;

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
   * Override constructor.
   *
   * Build peer and name for new contacts.
   */
  public function __construct($data = NULL) {
    parent::__construct($data);
    if (!isset($this->peer)) {
      if (isset($this->name)) {
        $this->peer = static::nameToPeer($name);
      }
      elseif (isset($this->first_name) && isset($this->last_name)) {
        $this->setFullName($this->first_name, $this->last_name);
      }
    }
  }

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
      return $this->peer = $this->nameToPeer($this->name);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Helper function. Convert contact name to peer name.
   */
  public static function nameToPeer($name) {
    return str_replace(' ', '_', $name);
  }

  /**
   * Set full name data (for creation).
   */
  public function setFullName($first_name, $last_name) {
    $this->first_name = $first_name;
    $this->last_name = $last_name;
    $this->name = trim($first_name) . '_' . trim($last_name);
    $this->peer = static::nameToPeer($this->name);
    return $this;
  }

}
