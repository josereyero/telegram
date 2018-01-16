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
  foreach (telegram_bot_manager()->getDefinitions() as $id => $info) {
    $group = 'telegram_bot_' . $id;
    $variables[$group . '_enable'] = array(
      'type' => 'boolean',
      'default' => FALSE,
      'title' => $info['label'],
      'group' => 'telegram_bot_enabled',
    );
    $variables[$group . '_name'] = array(
      'type' => 'string',
      'title' => t('Bot name', array(), $options),
      'group' => $group,
    );
    $variables[$group . '_token'] = array(
      'type' => 'string',
      'title' => t('Authentication token', array(), $options),
      'group' => $group,
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
  foreach (telegram_bot_manager()->getDefinitions() as $id => $info) {
    $groups['telegram_bot_' . $id] = array(
      'title' => $info['label'],
      'access' => 'administer site configuration',
    );
  }
  return $groups;
}