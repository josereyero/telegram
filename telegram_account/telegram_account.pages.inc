<?php

/**
 * User account page.
 */
function telegram_account_contact_page($account) {
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
        '#required' => TRUE,
      );
      $form['resend'] = array(
        '#type' => 'submit',
        '#value' => t('Resend verification code'),
      );
    }
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
    );
  }
  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  return $form;
}

/**
 * Telegram account contact form.
 *
 * @todo Add better phone validation
 */
function telegram_account_contact_form_validate($form, &$form_state) {
  $account = $form_state['account'];
  if ($form_state['values']['op'] == t('Save') && isset($form_state['values']['phone'])) {
    $phone = $form_state['values']['phone'];
    if (!is_numeric($phone) || strlen($phone) < 6) {
      form_set_error(t('Wrong phone number format'), 'phone');
    }
    elseif ($contact = telegram_manager()->getPhoneContact($phone)) {
      if ($contact->uid && $contact->uid != $account->uid) {
        form_set_error(t('This phone number is already taken.'), 'phone');
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

  switch ($form_state['values']['op']) {
    case t('Delete'):
      telegram_manager()->removeUserContact($account);
      drupal_set_message(t('The telegram contact has been deleted'));
      break;

    case t('Resend verification code');
      telegram_manager()->sendVerification($contact, TRUE);
      drupal_set_message(t('A new verification code has been sent.'));
      break;

    case t('Submit'):
      if (isset($form_state['values']['phone'])) {
        $contact = telegram_manager()->createUserContact($account, $form_state['values']['phone']);
        telegram_manager()->sendVerification($contact);
        drupal_set_message(t('The telephone has been saved and a verification code has been sent.'));
      }
      elseif (isset($form_state['values']['verify'])) {
        if (telegram_manager()->verifyContact($contact, $form_state['values']['verify'])) {
          drupal_set_message(t('Your phone number has been verified.'));
        }
        else {
          drupal_set_message(t('Wrong verification code.'), 'error');
        }
      }
      break;
  }
}

