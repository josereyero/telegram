<?php
/**
 * @file
 * Telegram variables.
 */

/**
 * Implements hook_variable_info().
 */
function telegram_bot_variable_info($options) {
  $variables = array();
  if ($manager = telegram_bot_manager()) {
    $bots = array();
    foreach ($manager->getDefinitions() as $id => $info) {
      $bots[$id] = $info['label'];
      $group = 'telegram_bot_' . $id;
      /*
      $variables[$group . '_enable_cron'] = array(
        'type' => 'boolean',
        'default' => FALSE,
        'title' => $info['label'],
        'group' => 'telegram_bot_enabled',
      );
      */
      $variables[$group . '_name'] = array(
        'type' => 'string',
        'title' => t('Bot user name', array(), $options),
        'group' => $group,
      );
      $variables[$group . '_token'] = array(
        'type' => 'string',
        'title' => t('Authentication token', array(), $options),
        'group' => $group,
      );
    }

    $variables['telegram_bot_enabled_cron'] = array(
      'title' => t('Enable cron', array(), $options),
      'type' => 'options',
      'options' => $bots,
      'group' => 'telegram_bot_enabled',
    );
    $variables['telegram_bot_enabled_webhook'] = array(
      'title' => t('Enable webhook', array(), $options),
      'type' => 'options',
      'options' => $bots,
      'group' => 'telegram_bot_enabled',
    );
  }
  return $variables;
}

/**
 * Implements hook_variable_group_info().
 */
function telegram_bot_variable_group_info($options) {
  $groups['telegram_bot_enabled'] = array(
    'title' => t('Enabled bots')
  );
  // Create a group for each bot.
  if ($manager = telegram_bot_manager()) {
    foreach ($manager->getDefinitions() as $id => $info) {
      $groups['telegram_bot_' . $id] = array(
        'title' => $info['label'],
        'access' => 'administer site configuration',
      );
    }
  }
  return $groups;
}