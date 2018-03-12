<?php

namespace Drupal\telegram\Storage;

use TelegramBot\Api\TypeInterface;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Contact;
use TelegramBot\Api\Types\User;
use TelegramBot\Api\Types\Chat;
use Symfony\Component\Validator\Constraints\Iban;

/**
 * Telegram object Storage
 *
 * Manages local storage for all Telegram objects.
 */
class TelegramObjectStorage {

  /**
   * Temporary storage for records.
   *
   * Records indexed by type and id or name.
   */
  protected $records_id;
  protected $records_name;

  /**
   * Save object to database.
   *
   * @param \TelegramBot\Api\TypeInterface $object;
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED
   */
  public function saveObject(TypeInterface $object, $owner = NULL) {
    if ($record = $this->mapObjectToRecord($object)) {
      return $this->saveRecord($record, $owner);
    }
    else {
      throw new \Exception(sprintf("Object cannot be mapped for storage: %s", get_class($object)));
    }
  }

  /**
   * Get Telegram object by id.
   */
  public function getObjectById($type, $id) {
    if ($record = $this->getRecordById($type, $id)) {
      return $record->object;
    }
    elseif ($record = $this->loadRecord(['type' => $type, 'id' => $id])) {
      return $record->object;
    }
  }

  /**
   * Get Telegram object by name.
   */
  public function getObjectByName($type, $name) {
    if ($record = $this->getRecordByName($type, $name)) {
      return $record->object;
    }
    elseif ($record = $this->loadRecord(['type' => $type, 'name' => $name])) {
      return $record->object;
    }
  }

  /**
   * Get multiple objects by conditions.
   */
  public function getMultipleObjects($conditions) {
    $list = array();
    foreach ($this->loadRecordMultiple($conditions) as $record) {
      $list[] = $record->object;
    }
    return $list;
  }

  /**
   * Save record to database.
   *
   * @param object $record
   *   Storage record.
   */
  protected function saveRecord($record, $owner = NULL) {
    $record = $this->prepareRecord($record);
    if (!empty($record->oid)) {
      if ($record->needs_updating) {
        $this->updateRecord($record);
      }
    }
    else {
      $this->createRecord($record);
    }

    if ($owner) {
      $this->setRecordOwner($record->oid, $owner);
    }

    return $record;
  }

  /**
   * Create new record in database.
   */
  protected function createRecord($record) {
    $this->prepareRecord($record);
    drupal_write_record('telegram_object', $record);
    $this->storeRecord($record);
  }

  /**
   * Update record in database.
   */
  protected function updateRecord($record) {
    $this->prepareRecord($record);
    drupal_write_record('telegram_object', $record, ['oid']);
  }

  /**
   * Prepare record for storage.
   *
   * @return object
   */
  protected function prepareRecord($record) {
    // Find existing record.
    if (empty($record->oid)) {
      if ($existing = $this->findExistingRecord($record)) {
        if ($existing->object == $record->object) {
          $existing->needs_updating = FALSE;
        }
        else {
          $existing->needs_updating = TRUE;
          $existing->object = $record->object;
          unset($existing->data);
        }
        $record = $existing;
      }
    }

    // Prepare data.
    if (empty($record->data)) {
      $record->data = $record->object->toJson();
    }
    // Set timestamps.
    $record->updated = REQUEST_TIME;
    if (empty($record->created)) {
      $record->created = $record->updated;
    }

    return $record;
  }

  /**
   * Find existing record.
   */
  protected function findExistingRecord($record) {
    $existing = NULL;
    if (!empty($record->id)) {
      $existing = $this->getRecordById($record->type, $record->id);
    }
    if (!$existing && !empty($record->name)) {
      $existing = $this->getRecordByName($record->type, $record->name);
    }
    return $existing;
  }

  /**
   * Set record owner.
   */
  protected function setRecordOwner($oid, $owner) {
    $fields = ['oid' => $oid, 'owner' => $owner];
    db_merge('telegram_object_owner')
      ->key($fields)
      ->fields($fields)
      ->execute();
  }

  /**
   * Get Telegram object by id.
   */
  protected function getRecordById($type, $id) {
    if (isset($this->record_ids[$type][$id])) {
      return $this->record_ids[$type][$id];
    }
    else {
      return $this->loadRecord(['type' => $type, 'id' => $id]);
    }
  }

  /**
   * Get Telegram object by name.
   */
  protected function getRecordByName($type, $name) {
    if (isset($this->records_name[$type][$name])) {
      return $this->records_name[$type][$name];
    }
    else {
      return $this->loadObject(['type' => $type, 'name' => $name]);
    }
  }

  /**
   * Store Record by id and name.
   */
  protected function storeRecord($record) {
    if (!empty($record->id)) {
      $this->records_id[$record->type][$record->id] = $record;
    }
    if (!empty($record->name)) {
      $this->records_name[$record->type][$record->name] = $record;
    }
  }

  /**
   * Load object form storage.
   *
   * @param array $conditions
   *   Array of field conditions.
   * @return object|NULL
   *   Storage record if found.
   */
  protected function loadRecord($conditions) {
    $records = $this->loadRecordMultiple($conditions);
    return reset($records);
  }

  /**
   * Load multiple objects from storage.
   * @param unknown $conditions
   *
   * @return array
   *   Array of storage records.
   */
  protected function loadRecordMultiple($conditions) {
    $query = db_select('telegram_object', 'o')
      ->fields('o', array());
    foreach ($conditions as $name => $value) {
      if ($name == 'owner') {
        $alias = $query->join('telegram_object_owner', 'w', 'o.oid = w.oid');
        $query->condition('w.owner', $value);
      }
      else {
        $query->condition('o.' . $name, $value);
      }
    }

    $list = array();
    foreach ($query->execute()->fetchAll() as $record) {
      $record->object = $this->mapRecordToObject($record);
      $this->storeRecord($record);
      $list[] = $record;
    }
    return $list;
  }

  /**
   * Map Telegram object to storage record.
   *
   * @param \TelegramBot\Api\TypeInterface $object;
   *
   * @return ojbect
   *   Record object.
   */
  protected function mapObjectToRecord(TypeInterface $object) {
    // Default name will be the lowercased class name.
    $class = get_class($object);
    $id = $name = $type = NULL;

    foreach ($this->getObjectMapping() as $type => $map) {
      list ($type_class, $getId, $getName) = $map;
      if ($class == $type_class) {
        $id = (int)$object->$getId();
        $name = $getName ? $object->$getName() : NULL;
        break;
      }
    }
    if ($type && ($id || $name)) {
      $record = (object)array(
        'oid' => NULL,
        'type' => $type,
        'id' => $id,
        'name' => $name,
        'object' => $object,
      );
      return $record;
    }
  }
  /**
   * Map record coming from DB to Telegram object.
   *
   * @param object $record
   *   Object loaded from Db
   */
  protected static function mapRecordToObject($record) {
    $mapping = static::getObjectMapping();

    if (isset($mapping[$record->type])) {
      list ($class, $getId, $getName) = $mapping[$record->type];
      $record->object = $class::fromResponse(json_decode($record->data, TRUE));
      return $record->object;
    }
    else {
      throw new \Exception(sprintf("Cannot map database object type %s", $record->type));
    }
  }

  /**
   * Get object mapping.
   *
   * @return array
   *   Object mapping indexed by object type [getId(), getName()]
   */
  public static function getObjectMapping() {
    $types['message'] = [Message::class, 'getMessageId', FALSE];
    $types['contact'] = [Contact::class, 'getUserId', 'getPhoneNumber'];
    $types['chat'] = [Chat::class, 'getId', 'getId'];
    $types['user'] = [User::class, 'getId', 'getUserName'];
    return $types;
  }

}
