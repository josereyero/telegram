#!/usr/bin/php
<?php
/**
 * Telegram test. Send message
 *
 * Usage:
 *   send.php Peer_Name Message
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
 * Include defaults and class loader.
 */
require_once 'include.php';

/**
 * Check input arguments
 */
$params = $argv;
array_shift($params);
$peer = array_shift($params);
$message = implode(' ', $params);

if (!$peer || !$message) {
  print "Usage:\n";
  print "  send.php Peer_Name Message\n";
  exit(1);
}
$client = telegram_create_client();

print "Sending Telegram message.\n";
$result = $client->sendMessage($peer, $message);
print "Result: $result\n";

print "\n\nDebug output:\n\n";
var_dump($client->getLogs());

?>