<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramData
 *
 * Base class for telegram data objects.
 */

namespace Drupal\telegram;

class TelegramData {

  /**
   * Construct from array.
   */
  public function __construct($data = NULL) {
    if ($data) {
      $this->setData($data);
    }
  }

  /**
   * Set data from array / object.
   */
  public function setData($data) {
    foreach ((array)$data as $name => $value) {
      $this->$name = $value;
    }
  }
}
