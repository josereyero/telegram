#!/usr/bin/php
<?php
/**
 * Telegram test.
 */

/**
 * Define some system dependent variables.
 */
define('TELEGRAM_COMMAND', '/usr/local/bin/telegram');
define('TELEGRAM_KEYFILE', '/etc/telegram/server.pub');
define('TELEGRAM_CONFIG', '/etc/telegram/telegram.conf');
// This one should contain an initialized .telegram folder
// That you can get by running telegram from the console.
define('TELEGRAM_HOMEPATH', '/home/telegram');

require_once 'lib/Drupal/telegram/TelegramProcess.php';
require_once 'lib/Drupal/telegram/TelegramClient.php';

use Drupal\telegram\TelegramClient;

// Create client.
$client = new TelegramClient(array(
    'command' => TELEGRAM_COMMAND,
    'keyfile' => TELEGRAM_KEYFILE,
    'configfile' => TELEGRAM_CONFIG,
    'homepath' => TELEGRAM_HOMEPATH,
));
$client->start();

//print "Contact list:\n\n";
//$contacts = $client->getContactList();
//var_dump($contacts);


$list = $client->getDialogList();
print "\nDialog list:\n";
var_dump($list);

$list = $client->getContactList();
print "\n\nContact list:\n\n";
var_dump($list);

// Close client.
$result = $client->stop();

//print "Debug output";
//var_dump($client->getRawOutput());

print "\nClient returned $result\n";

?>