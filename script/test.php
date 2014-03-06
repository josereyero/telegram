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
 * Include defaults and class loader.
 */
require_once 'include.php';

$client = telegram_create_client();

$client->start();

print "\nDialog list:\n";
$list = $client->getDialogList();
var_dump($list);

print "\n\nContact list:\n\n";
$list = $client->getContactList();
var_dump($list);

// Close client.
$result = $client->stop();

print "\n\nDebug output:\n\n";
var_dump($client->getLogs());

?>