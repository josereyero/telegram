<?php
/**
 * @file
 * Telegram administration.
 */

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
  $form['to'] = array(
    '#title' => t('To'),
    '#type' => 'textfield',
    '#size' => 20,
  );
  $form['text'] = array(
    '#title' => 'text',
    '#type' => 'textarea',
    '#default_value' => 'Test Message',
  );


  $form['send'] = array('#type' => 'submit', '#value' => t('Submit'));

  return $form;
}

/**
 * Test form submit.
 */
function telegram_test_form_submit($form, &$form_state) {
  $client = telegram_client();
  $to = $form_state['values']['to'];
  $text = $form_state['values']['text'];

  // Add contact list
  $form_state['output']['contacts'] = array(
    '#title' => t('Contacts'),
    '#type' => 'item',
    '#markup' => telegram_contact_list(),
  );
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
  if ($messages = $client->getAllMessages()) {
    $output['incoming'] = $messages;
  }
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

  $form_state['rebuild'] = TRUE;
  $client->stop();
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