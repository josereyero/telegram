<?php

/**
 * @file
 * Definition of Drupal/telegram/TelegramContact
 *
 * Encapsulates telegram messages.
 */

namespace Drupal\telegram;

/**
 * Log telegram status and debug messages
 */
class TelegramLogger {

  // Log level constants
  const DEBUG = 0;
  const INFO = 1;
  const NOTICE = 2;
  const WARNING = 3;
  const ERROR = 4;

  // Log level, defaults to NOTICE.
  protected $logLevel = 2;
  // Optional logging file.
  protected $logFile = NULL;
  // Store logs.
  protected $logs = array();

  /**
   * Class constructor.
   *
   * @param array $params
   *   Array with the following options.
   *   - 'log_level', Log level to use
   *   - 'log_file', Nave of the file for additional logging
   *   - 'debug', Force debug log level.
   */
  public function __construct($params = array()) {
    if (!empty($params['debug'])) {
      $this->logLevel = static::DEBUG;
    }
    elseif (isset($params['log_level'])) {
      $this->logLevel = (int)$params['log_level'];
    }
    if (!empty($params['log_file'])) {
      if ($file = fopen($params['log_file'], 'a')) {
        $this->logFile = $file;
      }
      else {
        $this->logError('Error opening log file', $params['logfile']);
      }
    }
    $this->logInfo('Telegram logger started');
  }

  /**
   * Log line in output.
   */
  public function logInfo($message, $args = NULL) {
    $this->log($message, $args, static::INFO);
  }

  /**
   * Log debug message.
   */
  public function logDebug($message, $args = NULL) {
    $this->log($message, $args, static::DEBUG);
  }

  /**
   * Log error message.
   */
  public function logError($message, $args = NULL) {
    $this->log($message, $args, static::ERROR);
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
   * Log message / mixed data.
   *
   * @param mixed $message
   */
  protected function log($message, $args, $severity) {
    if ($severity >= $this->logLevel) {
      $log = array(time(), $this->formatLevel($severity), $message, $this->formatString($args));
      $this->logs[] = $log;
      if ($this->logFile) {
        fwrite($this->logFile, implode(' ', $log) . "\n");
      }
    }
  }

  /**
   * Format log elements to display as table.
   */
  protected function formatLevel($level) {
    static $types;
    if (!isset($types)) {
      $types = array('DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR');
    }
    return isset($types[$level]) ? $types[$level] : 'UNKNOWN';
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

  /**
   * Class destructor.
   */
  public function __destruct() {
    $this->logInfo('Closing logger');
    if ($this->logFile) {
      fclose($this->logFile);
    }
  }

}
