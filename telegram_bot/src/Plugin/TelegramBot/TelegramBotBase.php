<?php

namespace Drupal\telegram_bot\Plugin\TelegramBot;

use Drupal\Component\Plugin\PluginBase;
use Drupal\telegram_bot\TelegramBotClient;
use Drupal\telegram_bot\TelegramBotApi;
use Drupal\telegram_bot\TelegramBotInterface;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\TypeInterface;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\Message;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @see https://github.com/TelegramBot/Api
 *
 * @see https://core.telegram.org/bots/api
 */
abstract class TelegramBotBase extends PluginBase implements TelegramBotInterface {

  /**
   * The bot token.
   *
   * @var string
   */
  protected $token;

  /**
   * The Telegram Bot Api
   *
   * @var \Drupal\telegram_bot\TelegramBotApi
   */
  protected $bot_api;

  /**
   * The Telegram Bot Client
   *
   * @var \Drupal\telegram_bot\TelebramBotClient
   */
  protected $bot_client;

  /**
   * Settings to pass to the process.
   *
   * @var Drupal\telegram\TelegramSettings
   */
  protected $settings;

  /**
   * The Telegram logger
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Last update id.
   *
   * @var int
   */
  protected $last_update_id;

  /**
   * Plugin constructor.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->settings = $configuration['settings'];
    $this->logger = $configuration['logger'];
    $this->token = $this->getSetting('token');

    $this->last_update_id = $this->getSetting('last_update_id', 0);
  }

  /**
   * {@inheritdoc}
   */
  public function invokeCron() {
    $last_update_id = $this->last_update_id;
    $this->processPendingUpdates();
    if ($this->last_update_id > $last_update_id) {
      $this->setSetting('last_update_id', $this->last_update_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWebhook() {
    $this->getBotClient()->run();
  }

  /**
   * {@inheritdoc}
   */
  public function validateKey($bot_key) {
    return $bot_key && ($webhook_key = $this->getWebhookKey()) && $bot_key === $webhook_key;
  }

  /**
   * Process pending updates.
   */
  public function processPendingUpdates($limit = 10) {
    // @todo Get from last update id.
    if ($updates = $this->getBotApi()->getUpdates($this->last_update_id + 1, $limit)) {
      $this->logger->info('Bot @id processing @count updates', ['@id' => $this->getPluginId(), '@count' => count($updates)]);
      foreach ($updates as $update) {
        $this->last_update_id = $update->getUpdateId();
        $this->processUpdate($update);
      }
    }
  }

  /**
   * Process update.
   *
   * @param \TelegramBot\Api\Types\Update $update
   */
  protected function processUpdate(Update $update) {
    if ($message = $update->getMessage()) {
      $this->processMessage($message);
    }
  }

  /**
   * Process update.
   *
   * @param \TelegramBot\Api\Types\Message $message
   */
  protected function processMessage(Message $message) {
    // The default implementation does nothing.
  }

  /**
   * Gets webhook key.
   *
   * @todo Should we make this system dependent?
   */
  protected function getWebhookKey() {
    return !empty($this->id) && !empty($this->token) ? md5($this->id . ':' . $this->token) : NULL;
  }

  /**
   * Gets the Bot Api
   *
   * @return \Drupal\telegram_bot\TelegramBotApi
   */
  public function getBotApi() {
    if (!isset($this->bot_api)) {
      $this->bot_api = new TelegramBotApi($this->token);
    }
    return $this->bot_api;
  }

  /**
   * Gets the Bot Client
   *
   * @return \Drupal\telegram_bot\TelegramBotClient
   */
  public function getBotClient() {
    if (!isset($this->bot_client)) {
      $this->bot_client = new TelegramBotClient($this->getBotApi());
    }
    return $this->bot_client;
  }

  /**
   * Save object to database.
   *
   * @param \TelegramBot\Api\TypeInterface $object;
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED
   */
  protected function saveObject(TypeInterface $object) {
    $owner = 'bot:' . $this->getPluginId();
    return $this->getObjectStorage()->saveObject($object, $owner);
  }

  /**
   * Get objects from storage that belong to this bot.
   *
   * @param array $conditions
   *   Array of field => value conditions.
   *
   * @return \TelegramBot\Api\TypeInterface[]
   *   Array of Telegram objects.
   */
  protected function getObjects($conditions = array()) {
    // Add 'owner' condition.
    $conditions += ['owner' => 'bot:' . $this->getPluginId()];
    return $this->getObjectStorage()->getMultipleObjects($conditions);
  }

  /**
   * Get variable value for this bot.
   *
   * Drupal variable name is 'telegram_bot_ID_NAME'
   */
  protected function getSetting($name, $default = NULL) {
    return $this->settings->get($this->pluginId . '_' . $name, $default);
  }

  /**
   * Set variable value for this bot.
   */
  protected function setSetting($name, $value) {
    $this->settings->set($this->pluginId . '_' . $name, $value);
    return $this;
  }

  /**
   * Gets the bot manager.
   *
   * @return \Drupal\telegram_bot\TelegramBotManager
   */
  protected function getBotManager() {
    return \Drupal::service('telegram_bot.manager');
  }

  /**
   * Get object storage.
   *
   * @return \Drupal\telegram\Storage\TelegramObjectStorage
   */
  protected function getObjectStorage() {
    return $this->getBotManager()->getObjectStorage();
  }
}
