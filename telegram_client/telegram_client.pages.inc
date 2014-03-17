<?php
/**
 * @file
 * Telegram client test pages.
 */

require_once drupal_get_path('module', 'telegram') . '/telegram.admin.inc';

/**
 * Send form.
 */
function telegram_client_send_form($form, &$form_state) {
  // Display output and logs.
  $form = _telegram_test_form($form, $form_state);

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
function telegram_client_send_form_submit($form, &$form_state) {
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
 * Test form.
 */
function telegram_client_contact_list_form($form, &$form_state) {
  // Display output and logs.
  $form = _telegram_test_form($form, $form_state);

  // Add contact list
  $form['contacts'] = array(
    '#title' => t('Contacts'),
    '#type' => 'item',
    '#markup' => telegram_client_contact_list(),
  );

  $form['send'] = array('#type' => 'submit', '#value' => t('Refresh'));

  return $form;
}

/**
 * Test form submit.
 */
function telegram_client_contact_list_form_submit($form, &$form_state) {
  $client = telegram_client();
  $client->getContactList();
  // Add debug output.
  _telegram_debug_form($form, $form_state);
  $form_state['rebuild'] = TRUE;
}

/**
 * Read messages form.
 */
function telegram_client_read_messages_form($form, &$form_state) {
  // Display output and logs.
  $form = _telegram_test_form($form, $form_state);

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
    '#markup' => telegram_client_read_messages($user),
  );

  $form['readmessages'] = array('#type' => 'submit', '#value' => t('Read'));

  return $form;
}

/**
 * Send form submit.
 */
function telegram_client_read_messages_form_submit($form, &$form_state) {
  // Add debug output.
  _telegram_debug_form($form, $form_state);

  $form_state['rebuild'] = TRUE;
}

/**
 * Print read messages .
 */
function telegram_client_read_messages($user) {
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
 * Print contact list.
 */
function telegram_client_contact_list() {
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
