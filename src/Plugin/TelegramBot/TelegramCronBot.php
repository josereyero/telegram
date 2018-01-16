<?php

namespace Drupal\telegram\Plugin\TelegramBot;

use Drupal\telegram\TelegramBot\TelegramBotCronInterface;
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
class TelegramCronBot extends TelegramBotBase implements TelegramBotCronInterface {

  /**
   * Last update id.
   *
   * @var int
   */
  protected $last_update_id;

  /**
   * {@inheritdoc}
   */
  public function invokeCron() {
    // @todo
  }

  /**
   * Receive incoming updates using long polling.
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
