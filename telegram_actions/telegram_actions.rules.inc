<?php
/**
 * @file
 * Provides Rules integration in order to post tweets.
 */

/**
 * Implements hook_rules_action_info() on behalf of the Telegram module.
 */
function telegram_actions_rules_action_info() {
  return array(
    'rules_core_telegram_actions_send_message_action' => array(
      'label' => t('Post a message to Telegram'),
      'group' => t('Telegram'),
      'parameter' => array(
        'message' => array(
          'type' => 'text',
          'label' => t('Message'),
          'description' => t("The content of the message."),
        ),
        'sender' => array(
          'type' => 'text',
          'label' => t('Sender'),
          'description' => t('Telegram account which will be used. ' .
            'By selecting [current user] the rule will check if the user ' .
            'has authenticated a Telegram account to use.'),
          'options list' => '_telegram_rules_account_options',
          'restriction' => 'input',
          'default mode' => 'input',
        ),
      ),
      'base' => 'telegram_actions_rules_send_message',
      'access callback' => 'rules_telegram_actions_access_callback',
    ),
  );
}

/**
 * Returns the list of Telegram accounts to be used for posting.
 *
 * @return
 *   an array of Telegram accounts.
 * @see telegram_actions_account_options()
 */
function _telegram_rules_account_options(RulesPlugin $element, $param_name) {
  return telegram_actions_account_options();
}

/**
 * Fetches Telegram account info and submits with the message to the Telegram API.
 *
 * @param $message
 *   The message to post
 * @param $sender
 *   The Drupal user that has a Telegram account
 */
function telegram_actions_rules_send_message($message, $sender) {
  if ($telegram_peer = _telegram_actions_get_telegram_peer($sender)) {
    try {
      telegram_actions_send_message($telegram_peer, $message);
      drupal_set_message(t('Successfully posted Telegram message'));
    }
    catch (TelegramException $e) {
      drupal_set_message(t('An error occurred when sending Telegram message: @message',
                           array('@message' => $e->getMessage())), 'warning');
    }
  }
}

/**
 * The callback function to access the condition
 */
function rules_telegram_actions_access_callback($type, $name) {
  return user_access('manage telegram contacts');
}
