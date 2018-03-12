<?php
/**
 * @file
 * Telegram include.php
 *
 * Quick start include for using Telegram CLI from PHP scripts
 */

/**
 * Define include path for classes
 */
define('TELEGRAM_CLASSPATH', __DIR__ .'/../lib');

/**
 * Define some defaults for system dependent variables.
 */
define('TELEGRAM_COMMAND', '/usr/local/bin/telegram');
define('TELEGRAM_KEYFILE', '/etc/telegram/server.pub');
define('TELEGRAM_CONFIG', '/etc/telegram/telegram.conf');
define('TELEGRAM_HOMEPATH', '/home/telegram');


use Drupal\telegram\TelegramProcess;
use Drupal\telegram\TelegramClient;

/**
 * Register class loader.
 */
spl_autoload_register(function ($class) {
  $class = str_replace('\\', '/', $class);
  require TELEGRAM_CLASSPATH .'/' . $class . '.php';
});

/**
 * Create telegram client.
 */
function telegram_create_client($params = array()) {
  $params += array(
      'command' => TELEGRAM_COMMAND,
      'keyfile' => TELEGRAM_KEYFILE,
      'configfile' => TELEGRAM_CONFIG,
      'homepath' => TELEGRAM_HOMEPATH,
  );

  $logger = telegram_logger();
  $process = new TelegramProcess($params, $logger);
  return new TelegramClient($process, $logger);
}
