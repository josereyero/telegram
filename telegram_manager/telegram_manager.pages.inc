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
      if ($contact = $telegram->getContactByPhone($to)) {
        $result = $telegram->sendToContact($contact, $text);
      }
      else {
        drupal_set_message('Contact not found', 'error');
      }
    }
    else {
      if ($contact = $telegram->getContactByName($to)) {
        $result = $telegram->sendToContact($contact, $text);
      }
      else {
        drupal_set_message('Contact not found', 'error');
      }
    }
    if ($result) {
      drupal_set_message(t('Your message has been queued for delivery.'));
    }
    $form_state['rebuild'] = TRUE;
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
  $form['read'] = array('#type' => 'submit', '#value' => t('Read new'));
  $form['refresh'] = array('#type' => 'submit', '#value' => t('Refresh all'));
  $form['delete'] = array('#type' => 'submit', '#value' => t('Delete all'));
  return $form;
}

/**
 * Send form submit.
 */
function telegram_manager_message_list_form_submit($form, &$form_state) {

  switch ($form_state['values']['op']) {
    case t('Refresh all'):
      $messages = telegram_manager()->refreshMessages(0, FALSE);
      break;
    case t('Read new'):
      $messages = telegram_manager()->refreshMessages(0, TRUE);
      break;
    case t('Delete all'):
      telegram_manager()->deleteMessages();
      drupal_set_message(t('Deleted all stored messages'));
      break;
  }

  if (isset($messages)) {
    $updated = isset($messages['updated']) ? count($messages['updated']) : 0;
    $created = isset($messages['created']) ? count($messages['created']) : 0;
    if ($created) {
      drupal_set_message(format_plural($created, 'Read 1 new message', 'Read @count new messages'));
    }
    if ($updated) {
      drupal_set_message(format_plural($updated, 'Updated 1 message', 'Updated @count messages'));
    }
    if (!$created && !$updated) {
      drupal_set_message(t('No new messages'));
    }
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
      check_plain($message->idmsg),
      format_date($message->created),
      check_plain($message->peer),
      check_plain($message->direction),
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
  $header = array(t('Id'), t('Peer'), t('Name'), t('Phone'));
  $rows = array();
  foreach ($contact_list as $contact) {
    $rows[] = array(
      check_plain($contact->idcontact),
      check_plain($contact->peer),
      check_plain($contact->name),
      check_plain($contact->phone),
    );
  }
  return theme('table', array('header' => $header, 'rows' => $rows));
}

