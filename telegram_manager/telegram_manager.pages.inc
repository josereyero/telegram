<?php
/**
 * @file
 * Telegram manager pages.
 */

require_once drupal_get_path('module', 'telegram') . '/telegram.admin.inc';

/**
 * Send form.
 */
function telegram_manager_send_form($form, &$form_state) {
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
function telegram_manager_send_form_submit($form, &$form_state) {
  $telegram = telegram_manager();
  $to = $form_state['values']['to'];
  $text = $form_state['values']['text'];

  if ($to && $text) {
    drupal_set_message(t('Sending'));
    if (is_numeric($to)) {
      $result = $client->sendToPhone($to, $text);
    }
    else {
      $peer = str_replace(' ', '_', $to);
      $result = $client->sendToPeer($peer, $text);
    }
    if ($result) {
      drupal_set_message(t('Your message has been queued for delivery.'));
    }
    $form_state['rebuild'] = TRUE;
    $telegram->stop();
  }


}

/**
 * Contact list page.
 */
function telegram_manager_contact_list_page() {
  $build['form'] = drupal_get_form('telegram_manager_contact_list_form');
  $build['contacts'] = array(
    '#title' => t('Contacts'),
    '#type' => 'item',
    '#markup' => telegram_manager_contact_list(),
  );
  return $build;
}

/**
 * Test form.
 */
function telegram_manager_contact_list_form($form, &$form_state) {
  // Display output and logs.
  $form = _telegram_test_form($form, $form_state);

  // Buttons
  $form['refresh'] = array('#type' => 'submit', '#value' => t('Refresh'));

  return $form;
}

/**
 * Test form submit.
 */
function telegram_manager_contact_list_form_submit($form, &$form_state) {
  $telegram = telegram_manager();
  $telegram->refreshContactList();
  $form_state['rebuild'] = TRUE;
}

/**
 * Contact list page.
 */
function telegram_manager_message_list_page() {
  $build['form'] = drupal_get_form('telegram_manager_message_list_form');
  $build['contacts'] = array(
    '#title' => t('Contacts'),
    '#type' => 'item',
    '#markup' => telegram_manager_message_list(),
  );
  return $build;
}
/**
 * Read messages form.
 */
function telegram_manager_message_list_form($form, &$form_state) {
  // Display output and logs.
  $form = _telegram_test_form($form, $form_state);

  // Buttons
  $form['refresh'] = array('#type' => 'submit', '#value' => t('Refresh'));

  return $form;
}

/**
 * Send form submit.
 */
function telegram_manager_message_list_form_submit($form, &$form_state) {

  switch ($form_state['values']['op']) {
    case t('Refresh'):
      $messages = telegram_manager()->readNewMessages();
      if ($messages) {
        drupal_set_message(format_plural(count($messages), '1 new message', '@count new messages'));
      }
      else {
        drupal_set_message(t('No new messages'));
      }
      break;
  }
  $form_state['rebuild'] = TRUE;
}


/**
 * Print read messages .
 */
function telegram_manager_message_list() {
  $message_list = telegram_manager()->getMessageList();
  $header = array(t('Id'), t('Date'), t('Peer'), t('Type'), t('Message'), t('Options'));
  $rows = array();
  foreach ($message_list as $message) {
    $rows[] = array(
      check_plain($message->oid),
      format_date($message->created),
      check_plain($message->peer),
      check_plain($message->type),
      check_plain($message->text),
      l(t('view'), 'admin/config/telegram/manager/message/' . $message->oid),
    );
  }
  $output = theme('table', array('header' => $header, 'rows' => $rows));
  return $output;
}

/**
 * Print contact list.
 */
function telegram_manager_contact_list() {
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

