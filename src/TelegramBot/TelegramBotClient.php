<?php

namespace Drupal\telegram\TelegramBot;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Events\EventCollection;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @see https://github.com/TelegramBot/Api
 *
 * @see https://core.telegram.org/bots/api
 */
class TelegramBotClient extends Client {

  /**
   * Client constructor
   *
   * @param \Drupal\telegram\TelegramBot\TelegramBotApi $api
   *   Telegram Bot API
   * @param string $token Telegram Bot API token
   * @param string|null $trackerToken Yandex AppMetrica application api_key
   */
  public function __construct($bot_api, $trackerToken = null) {
    $this->api = $bot_api;
    $this->events = new EventCollection($trackerToken);
  }
}
