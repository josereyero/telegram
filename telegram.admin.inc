<?php
/**
 * @file
 * Telegram administration.
 */

/**
 * Send form.
 */
function telegram_send_form($form, &$form_state) {
  if (isset($form_state['output'])) {
    $form['output'] = array(
      '#type' => 'fieldset',
      '#value' => 'output',
    ) + $form_state['output'];
  }
  $form['to'] = array(
    '#title' => t('To'),
    '#description' => t('Telegram user name or phone number.'),
    '#type' => 'textfield',
    '#size' => 20,
  );
  $form['text'] = array(
    '#title' => t('Message'),
    '#type' => 'textarea',
    '#default_value' => 'Test Message',
  );

  $form['send'] = array('#type' => 'submit', '#value' => t('Send'));

  return $form;
}

/**
 * Send form submit.
 */
function telegram_send_form_submit($form, &$form_state) {
  $client = telegram_client();
  $to = $form_state['values']['to'];
  $text = $form_state['values']['text'];

  if ($to && $text) {
    drupal_set_message(t('Sending'));
    if (is_numeric($to)) {
      $result = $client->sendToPhone($to, $text);
    }
    else {
      $to = $client->nameToPeer($to);
      $result = $client->sendToPeer($to, $text);
    }
    $output['result'] = $result;
  }

  // Add debug output.
  _telegram_debug_form($form, $form_state);

  $form_state['rebuild'] = TRUE;
  $client->stop();
}


/**
 * Read messages form.
 */
function telegram_read_messages_form($form, &$form_state) {
  $user = &$form_state['values']['user'];
  if (isset($form_state['output'])) {
    $form['output'] = array(
      '#type' => 'fieldset',
      '#value' => 'output',
    ) + $form_state['output'];
  }
  $form['user'] = array(
    '#title' => t('User'),
    '#description' => t('Telegram user name.'),
    '#type' => 'textfield',
    '#size' => 20,
  );

    // Add list messages
  $form['contacts'] = array(
    '#title' => t('Messages'),
    '#type' => 'item',
    '#markup' => telegram_read_messages($user),
  );

  $form['readmessages'] = array('#type' => 'submit', '#value' => t('Read'));

  return $form;
}


/**
 * Send form submit.
 */
function telegram_read_messages_form_submit($form, &$form_state) {
  // Add debug output.
  _telegram_debug_form($form, $form_state);

  $form_state['rebuild'] = TRUE;
}


/**
 * Test form.
 */
function telegram_contact_list_form($form, &$form_state) {
  if (isset($form_state['output'])) {
    $form['output'] = array(
      '#type' => 'fieldset',
      '#value' => 'output',
    ) + $form_state['output'];
  }

  // Add contact list
  $form['contacts'] = array(
    '#title' => t('Contacts'),
    '#type' => 'item',
    '#markup' => telegram_contact_list(),
  );

  $form['send'] = array('#type' => 'submit', '#value' => t('Refresh'));

  return $form;
}

/**
 * Test form submit.
 */
function telegram_contact_list_form_submit($form, &$form_state) {
  $client = telegram_client();
  $client->getContactList();
  // Add debug output.
  _telegram_debug_form($form, $form_state);
  $form_state['rebuild'] = TRUE;
}

/**
 * Test form.
 */
function telegram_test_form($form, &$form_state) {
  // If run for the first time, just start / stop process.
  if (isset($form_state['output'])) {
    $form['output'] = $form_state['output'];
  }

  $form += _telegram_test_logs();

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
 * Print contact list.
 */
function telegram_contact_list() {
  $contact_list = telegram_client()->getContactList();
  $header = array(t('Peer'), t('Name'), t('Phone'));
  $rows = array();
  foreach ($contact_list as $contact) {
    $rows[] = array(
      check_plain($contact->peer),
      check_plain($contact->name),
      check_plain($contact->phone),
    );
  }
  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Print read messages .
 */
function telegram_read_messages($user) {
  $read_messages = telegram_client()->getHistory($user);
  $header = array(t('Date'), t('Peer'), t('direction'), t('Message'));
  $rows = array();
  foreach ($read_messages as $message) {
    $rows[] = array(
      check_plain($message->date),
      check_plain($message->peer),
      check_plain($message->direction),
      check_plain($message->msg)
    );
  }
  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Add debug output to form state.
 */
function _telegram_debug_form($form, &$form_state, $output= array()) {
  $client = telegram_client();

  // Complete with other data.
  if ($process = $client->getProcess()) {
    $output['errors'] = $process->getErrors();
  }
  else {
    drupal_set_message('Telegram process cannot be started.', 'error');
  }
  $output['logs'] = $client->getLogs();

  foreach ($output as $title => $value) {

    $form_state['output'][$title] = array(
      '#type' => 'item',
      '#title' => $title,
      '#markup' => '<pre>' . $markup . '</pre>',
    );
  }
}

/**
 * Print logs
 */
function _telegram_debug_logs() {
  $element = array();
  if ($logs = _telegram_test_logs()) {

  }
  return $element;
}

/**
 * Build debug output for display in form.
 */
function _telegram_debug_build($output) {
  $form = array();
  if (isset($output['errors'])) {
    $form['errors'] = _telegram_debug_element(t('Errors'), $output['errors']);
  }
  if (isset($output['logs'])) {
    $form['logs'] = _telegram_debug_element(t('Logs'), $output['logs'], TRUE);
  }
  return $form;
}

/**
 * Build fieldset with debug data.
 */
function _telegram_debug_element($title, $value, $collapsed = FALSE) {
  $element = array (
    '#title' => $title,
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => $collapsed,
  );
  $markup = is_string($value) ? check_plain($value) : check_plain(print_r($value, TRUE));
  $element['value'] = array(
    '#type' => $markup,
    '#markup' => $markup,
  );
  return $element;
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
function _telegram_test_logs($string = NULL) {
  $element = array();
  if ($objects = drupal_static('telegram_manager')) {
    $logs = $objects['logger']->formatLogs();
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
