<?php
/**
 * @file
 * Telegram variables.
 */

/**
 * Implements hook_variable_info().
 */
function telegram_variable_info($options) {
  $group = 'telegram_settings';
  $variables['telegram_site_number'] = array(
    'type' => 'string',
    'title' => t('Site phone number', array(), $options),
    'group' => $group,
  );
  $variables['telegram_site_contact'] = array(
    'type' => 'string',
    'title' => t('Site contact name', array(), $options),
    'group' => $group,
  );
  $variables['telegram_debug'] = array(
    'type' => 'enable',
    'title' => t('Debug mode', array(), $options),
    'default' => 0,
    'group' => $group,
  );
  return $variables;
}

/**
 * Implements hook_variable_group_info().
 */
function telegram_variable_group_info($options) {
  $groups['telegram_settings'] = array(
    'title' => t('Telegram settings', array(), $options),
  );
  return $groups;
}