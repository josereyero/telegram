<?php

namespace Drupal\telegram\Plugin\TelegramBot;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use Drupal\Component\Plugin\PluginBase;
use Drupal\telegram\TelegramBot\TelegramBotClient;
use Drupal\telegram\TelegramBot\TelegramBotApi;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @see https://github.com/TelegramBot/Api
 *
 * @see https://core.telegram.org/bots/api
 */
abstract class TelegramBotBase extends PluginBase {

  /**
   * The bot token.
   *
   * @var string
   */
  protected $token;

  /**
   * The Telegram Bot Api
   *
   * @var \Drupal\telegram\TelegramBot\TelegramBotApi
   */
  protected $bot_api;

  /**
   * The Telegram Bot Client
   *
   * @var \Drupal\telegram\TelegramBot\TelebramBotClient
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
   * Plugin constructor.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->settings = $configuration['settings'];
    $this->logger = $configuration['logger'];
    $this->token = $this->getSetting('token');
  }


  /**
   * Gets the Bot Api
   *
   * @return \Drupal\telegram\TelegramBot\TelegramBotApi
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
   * @return \TelegramBot\Api\Client
   */
  public function getBotClient() {
    if (!isset($this->bot_client)) {
      $this->bot_client = new TelegramBotClient($this->getBotApi());
    }
    return $this->bot_client;
  }

  /**
   * Get variable value for this bot.
   *
   * Drupal variable name is 'telegram_bot_ID_NAME'
   */
  protected function getSetting($name, $default = NULL) {
    return variable_get($this->pluginId . '_' . $name, $default);
  }

  /**
   * Set variable value for this bot.
   */
  protected function setSetting($name, $value) {
    $this->settings->set($this->pluginId . '_' . $name, $value);
    return $this;
  }

  /**
   * Magic method call, invoke Telegram Bot API
   *
   * @param unknown $name
   * @param array $arguments
   * @throws BadMethodCallException
   * @return mixed
   */
  public function __call($name, array $arguments) {
    $api = $this->getBotApi();

    if (method_exists($api, $name)) {
      return call_user_func_array([$api, $name], $arguments);
    }

    throw new \BadMethodCallException(sprintf("Method %s doesn't exist", $name));
  }

}
