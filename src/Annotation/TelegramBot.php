<?php

namespace Drupal\telegram\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a connection type class.
 *
 * Plugin Namespace: Plugin/TelegramBot
 *
 * @Annotation
 */
class TelegramBot extends Plugin {

 /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the connection type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The human-readable name of the connection type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;
}
