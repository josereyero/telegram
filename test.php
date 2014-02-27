#!/usr/bin/php
<?php
/**
 * Telegram test.
 */

require_once 'lib/Drupal/telegram/TelegramProcess.php';
require_once 'lib/Drupal/telegram/TelegramClient.php';

use Drupal\telegram\TelegramClient;

// Create client.
$client = new TelegramClient();
$client->start();

//print "Contact list:\n\n";
//$contacts = $client->getContactList();
//var_dump($contacts);


$list = $client->getDialogList();
print "Dialog list:\n\n";
var_dump($list);


// Close client.
$result = $client->stop();

//print "Debug output";
//var_dump($client->getRawOutput());

print "\nClient returned $result\n";

?>