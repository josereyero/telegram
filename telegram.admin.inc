<?php
/**
 * @file
 * Telegram administration.
 */


/**
 * Test form.
 */
function telegram_test_form($form, &$form_state) {
  // Display output and logs.
  $form = _telegram_test_form($form, $form_state);

  $form['dialogs'] = array('#type' => 'submit', '#value' => t('Dialog list'));
  $form['contacts'] = array('#type' => 'submit', '#value' => t('Contact list'));

  return $form;
}

/**
 * Test form submit.
 */
function telegram_test_form_submit($form, &$form_state) {
  if ($client = _telegram_test_client()) {
    switch ($form_state['values']['op']) {
      case t('Dialog list'):
        $data = $client->getDialogList();
        break;
      case t('Contact list'):
        $data = $client->getContactList();
        break;
    }
    $output = array(
      '#type' => 'fieldset',
      '#title' => $form_state['values']['op'],
    );
    $output['output']['content'] = array(
      '#type' => 'markup',
      '#markup' => '<pre>' . check_plain(print_r($data, TRUE)) . '</pre>',
    );
    $form_state['output'] = $output;
    $form_state['rebuild'] = TRUE;
    $client->stop();
  }

}

/**
 * Base form for testing and logs.
 */

function _telegram_test_form($form, &$form_state) {
  if (isset($form_state['output'])) {
    $form['output'] = $form_state['output'];
  }
  $form += _telegram_test_logs();

  return $form;
}

/**
 * Get telegram client with some messages.
 */
function _telegram_test_client() {
  static $client;
  if (!isset($client)) {
    $client = telegram_client();
    if ($client->start()) {
      drupal_set_message(t('Telegram client started successfully'));
    }
    else {
      drupal_set_message(t('Cannot start Telegram client'), 'error');
      $client = FALSE;
    }
  }
  return $client;
}

/**
 * Build element with telegram logs
 */
function _telegram_test_logs() {
  $element = array();
  if ($logs = telegram_logger()->formatLogs()) {
    $element['logs'] = array(
      '#type' => 'fieldset',
      '#title' => t('Log messages'),
      '#collapsible' => TRUE, '#collapsed' => TRUE,
    );
    $element['logs']['content'] = array(
      '#type' => 'markup',
      '#markup' => '<pre>'. check_plain($logs) . '</pre>',
    );
  }
  return $element;
}
