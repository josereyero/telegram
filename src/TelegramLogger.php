<?php

namespace Drupal\telegram;

use Drupal\service_container\Logger\LoggerBase;

/**
 * Log telegram status and debug messages
 */
class TelegramLogger extends LoggerBase {

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
    $log = array(time(), $this->formatLevel($level), $message, $this->formatString($context));
    $this->logs[] = $log;
  }

  /**
   * {@inheritdoc}
   */
  public function debug($message, array $context = array()) {
    if ($this->debug) {
      parent::debug($message, $context);
    }
  }

  /**
   * Log line in output.
   */
  public function logInfo($message, $args = array()) {
    if (!is_array($args)) {
      debug_print_backtrace();
    }
    $this->info($message, $args);
  }

  /**
   * Log debug message.
   */
  public function logDebug($message, $args = array()) {
    $this->debug($message, $args);
  }

  /**
   * Log error message.
   */
  public function logError($message, $args = array()) {
    if (!is_array($args)) {
      debug_print_backtrace();
    }
    $this->error($message, $args);
  }

  /**
   * Get raw logs.
   */
  public function getLogs() {
    return $this->logs;
  }

  /**
   * Get formatted logs.
   */
  public function formatLogs() {
    $output = '';
    foreach ($this->logs as $log) {
      $output .= implode(' ', $log) . "\n";
    }
    return $output;
  }

  /**
   * Format log elements to display as table.
   */
  protected function formatLevel($level) {
    static $types;
    if (!isset($types)) {
      $types = array('DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR');
    }
    if (is_int($level)) {
      return isset($types[$level]) ? $types[$level] : 'UNKNOWN';
    }
    else {
      return $level;
    }
  }

  /**
   * Format any data as string.
   */
  protected function formatString($data) {
    if (is_scalar($data)) {
      return (string)$data;
    }
    else {
      return print_r($data, TRUE);
    }
  }

}
