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
      $result = telegram_send_phone($to, $text);
    }
    else {
      $to = $client->nameToPeer($to);
      $result = telegram_send_peer($to, $text);
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
  telegram_manager()->refreshContactList();
  // Add debug output.
  _telegram_debug_form($form, $form_state);
  $form_state['rebuild'] = TRUE;
}

/**
 * Test form.
 */
function telegram_test_form($form, &$form_state) {
  if (isset($form_state['output'])) {
    $form['output'] = array(
      '#type' => 'fieldset',
      '#value' => 'output',
    ) + $form_state['output'];
  }

  $form['send'] = array('#type' => 'submit', '#value' => t('Check'));

  return $form;
}

/**
 * Test form submit.
 */
function telegram_test_form_submit($form, &$form_state) {
  $client = telegram_client();

  $output['dialogs'] = $client->getDialogList();
  $output['contacts'] = $client->getContactList();

  // Add debug output.
  _telegram_debug_form($form, $form_state, $output);
  $form_state['rebuild'] = TRUE;
}

/**
 * Print contact list.
 */
function telegram_contact_list() {
  $contact_list = telegram_manager()->getContactList();
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
  $output['errors'] = $client->getProcess()->getErrors();
  $output['logs'] = $client->getProcess()->getLogs();

  foreach ($output as $title => $value) {
    $markup = is_string($value) ? check_plain($value) : check_plain(print_r($value, TRUE));
    $form_state['output'][$title] = array(
      '#type' => 'item',
      '#title' => $title,
      '#markup' => '<pre>' . $markup . '</pre>',
    );
  }
}