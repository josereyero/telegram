<?php

namespace Drupal\telegram;

/**
 * Service to Handle Telegram Settings.
 */
class TelegramSettings {

  /**
   * The variable prefix
   *
   * @var string
   */
  protected $prefix;

  /**
   * Constructor (other services cannot be injected).
   *
   * @param \Drupal\service_container\Legacy\Drupal7 $drupal7
   *   The Drupal 7 legacy service.
   */
  public function __construct($prefix = '') {
    $this->prefix = $prefix;
  }

  public function get($name, $default = NULL) {
    return variable_get_value($this->prefix . $name, ['default' => $default]);
  }

  public function set($name, $value) {
    variable_set_value($this->prefix . $name, $value);
  }
}