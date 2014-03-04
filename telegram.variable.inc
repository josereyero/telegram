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
    'title' => t('Site phone number', array(), $options),
  );
  $variables['telegram_site_contact'] = array(
    'type' => 'string',
    'title' => t('Site contact name', array(), $options),
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
  $variables['telegram_config_path'] = array(
    'type' => 'string',
    'title' => t('Configuration file path', array(), $options),
    'default' => '/etc/telegram/telegram.conf',
  );
  $variables['telegram_command_cwd'] = array(
    'type' => 'string',
    'title' => t('Command cwd path', array(), $options),
    'default' => '/home/telegram',
  );
  $variables['telegram_command_debug'] = array(
    'type' => 'enable',
    'title' => t('Debug mode', array(), $options),
    'default' => 0,
  );
  return $variables;
}