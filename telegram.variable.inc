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
  );
  return $variables;
}