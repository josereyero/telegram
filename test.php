#!/usr/bin/php
<?php
/**
 * Telegram test.
 *
 * This is a demo/test script to be run from the command line.
 *
 * The TelegramClient can be used without Drupal too.
 */

/* Start: Remove */
print "Telegram Client demo.\n";
print "Edit and remove these lines to run from the command line.\n";
print "DON'T DO IT ON A PUBLIC WEB SERVER FOLDER\n";
exit(0);
/* End: Remove */

/**
 * Define some system dependent variables.
 */
define('TELEGRAM_COMMAND', '/usr/local/bin/telegram');
define('TELEGRAM_KEYFILE', '/etc/telegram/server.pub');
define('TELEGRAM_CONFIG', '/etc/telegram/telegram.conf');
define('TELEGRAM_HOMEPATH', '/home/telegram');
// Enable debug mode.
define('TELEGRAM_DEBUG', 1);

require_once 'lib/Drupal/telegram/TelegramProcess.php';
require_once 'lib/Drupal/telegram/TelegramClient.php';

use Drupal\telegram\TelegramClient;

// Create client.
$client = new TelegramClient(array(
    'command' => TELEGRAM_COMMAND,
    'keyfile' => TELEGRAM_KEYFILE,
    'configfile' => TELEGRAM_CONFIG,
    'homepath' => TELEGRAM_HOMEPATH,
    'debug' => TELEGRAM_DEBUG,
));
$client->start();

$list = $client->getDialogList();
print "\nDialog list:\n";
var_dump($list);

$list = $client->getContactList();
print "\n\nContact list:\n\n";
var_dump($list);

print "\n\nDebug output:\n\n";
var_dump($client->getProcess()->getLogs());

// Close client.
$result = $client->stop();

//print "Debug output";
//var_dump($client->getRawOutput());

print "\nClient returned $result\n";

?>