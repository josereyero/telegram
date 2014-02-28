<?php
/**
 * @file
 * Telegram variables.
 */

/**
 * Implements hook_variable_info().
 */
function telegram_variable_info($options) {
  $variables['telegram_site_number'] = array(
    'type' => 'string',
    'title' => t('Site number', array(), $options),
  );
  $variables['telegram_command_exec'] = array(
    'type' => 'string',
    'title' => t('Executable command', array(), $options),
    'default' => '/usr/local/bin/telegram',
  );
  $variables['telegram_command_key'] = array(
    'type' => 'string',
    'title' => t('Key file path', array(), $options),
    'default' => '/etc/telegram/server.pub',
  );
  $variables['telegram_command_debug'] = array(
    'type' => 'enable',
    'title' => t('Debug mode', array(), $options),
    'default' => 0,
  );
  return $variables;
}