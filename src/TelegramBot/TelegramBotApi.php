<?php

namespace Drupal\telegram\TelegramBot;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\ArrayOfUpdates;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @see https://github.com/TelegramBot/Api
 *
 * @see https://core.telegram.org/bots/api
 */
class TelegramBotApi extends BotApi {

  /**
   * Last update id.
   *
   * @var int
   */
  protected $last_update_id;

  /**
   * Constructor
   *
   * @param string $token Telegram Bot API token
   * @param string|null $trackerToken Yandex AppMetrica application api_key
   */
  public function __construct($token, $trackerToken = null, $settings = array()) {
    parent::__construct($token, $trackerToken);

    $this->last_update_id = $this->getSetting('last_update_id');
  }

  /**
   * Receive incoming updates using long polling.
   *
   * @todo Remove once BotApi updated to next version
   *
   * @see https://core.telegram.org/bots/api#getupdates
   *
   * @param int $offset
   * @param int|null $limit
   * @param int|null $timeout
   * @param array $allowed_updates
   *
   * @return \TelegramBot\Api\Types\Update[]
   *   Array of Update objects.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function getUpdates($offset = NULL, $limit = 100, $timeout = 0, $allowed_updates = array()) {
    return ArrayOfUpdates::fromResponse($this->call('getUpdates', [
        'offset' => $offset,
        'limit' => $limit,
        'timeout' => $timeout,
        'allowed_updates' => $allowed_updates,
    ]));
  }

}
