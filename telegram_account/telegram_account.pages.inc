<?php

/**
 * User account edit page.
 */
function telegram_account_edit_page($account) {
  return drupal_get_form('telegram_account_contact_form', $account);
}

/**
 * Telegram account contact form.
 */
function telegram_account_contact_form($form, &$form_state, $account) {
  $contact = isset($account->telegram) ? $account->telegram : NULL;
  $form_state['account'] = $account;
  $form['phone'] = array(
    '#title' => t('Phone number'),
    '#type' => 'textfield',
    '#size' => 12,
    '#default_value' => $contact ? $contact->phone : '',
    '#required' => TRUE,
  );

  if ($contact) {
    $form['phone']['#disabled'] = TRUE;
    if (empty($contact->verified)) {
      // Contact not verified, lock phone.

      $form['verify'] = array(
        '#title' => t('Enter verification code'),
        '#type' => 'textfield',
        '#size' => 12,
        '#default_value' => '',
      );
     $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Verify phone'),
        '#weight' => 20,
      );
      $form['resend'] = array(
        '#type' => 'submit',
        '#value' => t('Resend verification code'),
        '#weight' => 30,
      );
    }
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#weight' => 20,
    );
  }
  else {
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#weight' => 10,
    );
  }

  return $form;
}

/**
 * Telegram account contact form.
 *
 * @todo Add better phone validation
 */
function telegram_account_contact_form_validate($form, &$form_state) {
  $account = $form_state['account'];
  if ($form_state['values']['op'] == t('Submit')) {
    $phone = $form_state['values']['phone'];
    if (!is_numeric($phone) || strlen($phone) < 6) {
      form_set_error('phone', t('Wrong phone number format'));
    }
    elseif ($contact = telegram_manager()->getContactByPhone($phone)) {
      if ($contact->uid && $contact->uid != $account->uid) {
        form_set_error('phone', t('This phone number is already taken.'));
      }
    }
  }
}

/**
 * Telegram account contact form.
 */
function telegram_account_contact_form_submit($form, &$form_state) {
  $account = $form_state['account'];
  $contact = isset($account->telegram) ? $account->telegram : NULL;
  $telegram = telegram_manager();

  switch ($form_state['values']['op']) {
    case t('Delete'):
      $contact->uid = 0;
      $contact->verified = 0;
      $telegram->saveContact($contact);
      drupal_set_message(t('The telegram contact has been deleted'));
      break;

    case t('Resend verification code');
      telegram_account_send_verification($contact);
      drupal_set_message(t('A new verification code has been sent.'));
      break;

    case t('Submit'):
      if (isset($form_state['values']['phone'])) {
        $contact = telegram_account_create_contact($account, $form_state['values']['phone']);
        telegram_account_send_verification($contact);
        drupal_set_message(t('The telephone has been saved and a verification code has been sent.'));
      }
      break;

    case t('Verify phone'):
      if (telegram_account_verify_contact($contact, $form_state['values']['verify'])) {
        drupal_set_message(t('Your phone number has been verified.'));
      }
      else {
        drupal_set_message(t('Wrong verification code.'), 'error');
      }
      break;
  }
}

/**
 * User account edit page.
 */
function telegram_account_send_page($account) {
  drupal_set_title('Send telegram message to @name', array('@name' => $account->name));
  return drupal_get_form('telegram_account_send_form', $account);
}

/**
 * Message send form
 */
function telegram_account_send_form($form, $form_state, $account) {
  $form_state['account'] = $account;
  $form['text'] = array(
    '#title' => t('Message'),
    '#type' => 'textarea',
    '#default_value' => '',
    '#required' => TRUE,
  );

  $form['send'] = array('#type' => 'submit', '#value' => t('Send'));

  return $form;
}

/**
 * Message send form
 */
function telegram_account_send_form_submit($form, $form_state) {
  $account = $form_state['account'];
  if (!empty($account->telegram)) {
    $result = telegram_manager()->sendMessage($account->telegram->peer, $form_state['values']['text']);
    drupal_set_message(t('Your message has been queued for delivery.'));
  }
}


