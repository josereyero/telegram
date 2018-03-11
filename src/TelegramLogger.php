<?php

namespace Drupal\telegram;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Log telegram status and debug messages
 */
class TelegramLogger implements LoggerInterface {
  use RfcLoggerTrait;

  // Log level constants
  const DEBUG = 0;
  const INFO = 1;
  const NOTICE = 2;
  const WARNING = 3;
  const ERROR = 4;

  /**
   * Enable debug logging.
   *
   * @var boolean
   */
  protected $debug = FALSE;

  /**
   * Drupal logger channel.
   *
   * @var \Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * Constructs a TelegramLogger object
   *
   * @param string $channel
   *   The channel name for this instance.
   */
  public function __construct($channel) {
    $this->logger = \Drupal::service('logger.factory')->get($channel);
    // Cannot use variable_get_value(), causes a ServiceCircularReferenceException
    $this->debug = variable_get('telegram_command_debug', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    $this->logger->log($level, $message, $context);
  }

}
