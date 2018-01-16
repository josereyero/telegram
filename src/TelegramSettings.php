<?php

namespace Drupal\telegram;

use Drupal\service_container\Legacy\Drupal7;
use Drupal\service_container\Variable;

/**
 * Service to Handle Telegram Settings.
 */
class TelegramSettings {

  /**
   * The Drupal7 service.
   *
   * @var \Drupal\service_container\Legacy\Drupal7
   */
  protected $drupal7;

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
    $this->drupal7 = \Drupal::service('drupal7');
    $this->prefix = $prefix;
  }

  public function get($name, $default = NULL) {
    return $this->drupal7->variable_get_value($this->prefix . $name, ['default' => $default]);
  }

  public function set($name, $value) {
    $this->drupal7->variable_set_value($this->prefix . $name, $value);
  }
}