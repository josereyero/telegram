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
      $contact = $telegram->getContactByPhone($to);
    }
    else {
      $contact = $telegram->getContactByName($to);
    }
    if (!empty($contact)) {
      $result = $telegram->sendMessage($contact->peer, $text);
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
  $build['contacts'] = telegram_manager_contact_list();
  return $build;
}

/**
 * Contact page.
 */
function telegram_manager_contact_page($contact) {
  $build['contact'] = array(
    '#type' => 'fieldset',
    '#title' => $contact->name,
  );
  $build['contact']['peer'] = array(
    '#title' => t('Peer'),
    '#type' => 'item',
    '#markup' => check_plain($contact->peer),
  );
  $build['contact']['phone'] = array(
    '#title' => t('Phone'),
    '#type' => 'item',
    '#markup' => check_plain($contact->phone),
  );
  $build['messages'] = telegram_manager_message_list(array('peer' => $contact->peer));

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
  $build['content'] = telegram_manager_message_list();
  return $build;
}

/**
 * Message page.
 */
function telegram_manager_message_page($message) {
  $build['message'] = array(
    '#type' => 'fieldset',
    '#title' => t('Telegram Message @id', array('@id' => $message->idmsg)),
  );
  $build['message']['type'] = array(
    '#title' => t('Type'),
    '#type' => 'item',
    '#markup' => check_plain($message->direction),
  );
  $build['message']['status'] = array(
    '#title' => t('Status'),
    '#type' => 'item',
    '#markup' => $message->formatStatus(),
  );
  $build['message']['peer'] = array(
    '#title' => t('Peer'),
    '#type' => 'item',
    '#markup' => check_plain($message->peer),
  );
  $build['message']['created'] = array(
    '#title' => t('Created'),
    '#type' => 'item',
    '#markup' => format_date($message->created),
  );
  $build['message']['sent'] = array(
    '#title' => t('Sent'),
    '#type' => 'item',
    '#markup' => format_date($message->sent),
  );
  $build['message']['updated'] = array(
    '#title' => t('Updated'),
    '#type' => 'item',
    '#markup' => format_date($message->updated),
  );
  $build['message']['text'] = array(
    '#title' => t('Text'),
    '#type' => 'item',
    '#markup' => '<pre>' . check_plain($message->text) . '</pre>',
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
function telegram_manager_message_list($conditions = array(), $options = array()) {
  $options += array(
    'order' => array('created' => 'DESC'),
    'limit' => 20,
  );
  $message_list = telegram_manager()->getMessages($conditions, $options);
  $header = array(t('Id'), t('Date'), t('Peer'), t('Type'), t('Message'), t('Status'), t('Options'));
  $rows = array();
  foreach ($message_list as $message) {
    $rows[] = array(
      check_plain($message->idmsg),
      format_date($message->created),
      check_plain($message->peer),
      check_plain($message->direction),
      check_plain(truncate_utf8($message->text, 40, TRUE, TRUE)),
      $message->formatStatus(),
      l(t('view'), 'admin/config/telegram/manager/message/' . $message->oid),
    );
  }
  $build['data'] = array(
   '#theme' => 'table',
   '#header' => $header,
   '#rows' => $rows
  );
  $build['pager'] = array(
    '#theme' => 'pager',
    '#weight' => 5,
  );
  return $build;
}

/**
 * Print contact list.
 */
function telegram_manager_contact_list($conditions = array(), $options = array()) {

  $options += array(
    'order' => array('name' => 'ASC'),
    'limit' => 20,
  );
  $contact_list = telegram_manager()->getContacts($conditions, $options);
  $header = array(t('Id'), t('Peer'), t('Name'), t('Phone'), t('Options'));
  $rows = array();
  foreach ($contact_list as $contact) {
    $rows[] = array(
      check_plain($contact->idcontact),
      check_plain($contact->peer),
      check_plain($contact->name),
      check_plain($contact->phone),
      l(t('view'), 'admin/config/telegram/manager/contact/' . $contact->oid),
    );
  }
  $build['data'] = array(
   '#theme' => 'table',
   '#header' => $header,
   '#rows' => $rows
  );
  $build['pager'] = array(
    '#theme' => 'pager',
    '#weight' => 5,
  );
  return $build;
}

/**
 * Edit contact form.
 */
function telegram_manager_contact_form($form, &$form_state, $contact) {
  $form_state['contact'] = $contact;
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $contact->name,
  );
  $form['phone'] = array(
    '#type' => 'textfield',
    '#title' => t('Phone'),
    '#default_value' => $contact->phone,
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  return $form;
}

/**
 * Edit contact. Form submission.
 */
function telegram_manager_contact_form_submit($form, &$form_state) {
  drupal_set_message('NOT IMPLEMENTED');
}

