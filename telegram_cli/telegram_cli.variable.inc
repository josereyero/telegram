<?php
/**
 * @file
 * Telegram variables.
 */

/**
 * Implements hook_variable_info().
 */
function telegram_cli_variable_info($options) {
  $group = 'telegram_settings';
  $variables['telegram_command_exec'] = array(
    'type' => 'string',
    'title' => t('Executable command', array(), $options),
    'default' => '/usr/local/bin/telegram',
      'group' => $group,
  );
  $variables['telegram_command_key'] = array(
    'type' => 'string',
    'title' => t('Key file path', array(), $options),
    'default' => '/etc/telegram/server.pub',
      'group' => $group,
  );
  $variables['telegram_config_path'] = array(
    'type' => 'string',
    'title' => t('Configuration file path', array(), $options),
    'default' => '/etc/telegram/telegram.conf',
      'group' => $group,
  );
  $variables['telegram_command_cwd'] = array(
    'type' => 'string',
    'title' => t('Command cwd path', array(), $options),
    'default' => '/home/telegram',
      'group' => $group,
  );
  $variables['telegram_command_debug'] = array(
    'type' => 'enable',
    'title' => t('CLI Debug mode', array(), $options),
    'default' => 0,
      'group' => $group,
  );
  return $variables;
}