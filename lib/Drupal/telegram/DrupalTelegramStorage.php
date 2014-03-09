<?php

/**
 * @file
 * Definition of Drupal/telegram/DrupalTelegramStorage
 */

namespace Drupal\telegram;

use \PDO;

/**
 * Drupal Telegram Storage
 *
 * Manages local storage for all Telegram objects.
 */
class DrupalTelegramStorage {

  /**
   * Load single contact
   */
  function contactLoad($oid) {
    $objects = $this->contactLoadMultiple(array('oid' => $oid));
    return reset($objects);
  }

  /**
   * Load single contact
   */
  function contactLoadMultiple($conditions, $index = 'phone') {
    return $this->loadMultiple('telegram_contact', $conditions, $index, '\Drupal\telegram\TelegramContact');
  }

  /**
   * Save object
   */
  function contactSave($contact) {
    return $this->save('telegram_contact', $contact);

  }

  /**
   * Create contact.
   */
  function contactDelete($contact) {
    return $this->delete('telegram_contact', $contact);
  }

  /**
   * Load single contact
   */
  function messageLoad($oid) {
    $objects = $this->contactLoadMultiple(array('oid' => $oid));
    return reset($objects);
  }

  /**
   * Load multiple messages
   */
  function messageLoadMultiple($conditions, $index = 'oid') {
    return $this->loadMultiple('telegram_message', $conditions, $index, '\Drupal\telegram\TelegramMessage');
  }

  /**
   * Save object
   */
  function messageSave($contact) {
    return $this->save('telegram_message', $contact);

  }

  /**
   * Delete message.
   */
  function messageDelete($message) {
    return $this->delete('telegram_message', $contact);
  }

  /**
   * Delete multiple messages.
   */
  function messageDeleteAll($conditions = array()) {
    return $this->deleteMultiple('telegram_message', $conditions);
  }

  /**
   * Save generic object.
   */
  protected function save($table, $object) {
    if (isset($object->oid)) {
      return $this->update($table, $object);
    }
    else {
      return $this->insert($table, $object);
    }
  }

  /**
   * Create generic object.
   */
  protected function insert($table, $object) {
    $this->created = $this->updated = REQUEST_TIME;
    return drupal_write_record($table, $object);
  }

  /**
   * Update generic object.
   */
  protected function update($table, $object) {
    $this->updated = REQUEST_TIME;
    drupal_write_record($table, $object, array('oid'));
  }

  /**
   * Delete object
   */
  protected function delete($table, $object) {
    if (isset($object->oid)) {
      $this->updated = REQUEST_TIME;
      db_delete($table)->condition('oid', $object->oid)->execute();
      $object->oid = NULL;
    }
  }

  /**
   * Delete multiple objects.
   */
  protected function deleteMultiple($table, $conditions) {
    $query = db_delete($table);
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    return $query->execute();
  }

  /**
   * Load objects
   */
  protected function loadMultiple($table, $conditions, $index, $class) {
    $query = db_select($table, 't')->fields('t', array());
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $result = $query->execute();
    $result->setFetchMode(PDO::FETCH_CLASS, $class);
    $list = array();
    foreach ($result->fetchAll() as $object) {
      $list[$object->$index] = $object;
    };
    return $list;
  }

}
