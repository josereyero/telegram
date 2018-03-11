<?php

namespace Drupal\telegram_bot\Plugin\TelegramBot;

use Drupal\telegram_bot\TelegramBotWebhookInterface;

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
class TelegramWebhookBot extends TelegramBotBase implements TelegramBotWebhookInterface {

  /**
   * Last update id.
   *
   * @var int
   */
  protected $last_update_id;

  /**
   * {@inheritdoc}
   */
  public function validateKey($bot_key) {
    return $bot_key && ($webhook_key = $this->getWebhookKey()) && $bot_key === $webhook_key;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWebhook() {
    $this->getBotClient()->run();
  }

  /**
   * Magic method call, invoke Telegram Bot API
   *
   * @param string $name
   * @param array $arguments
   * @throws \BadMethodCallException
   * @return mixed
   */
  public function __call($name, array $arguments) {
    $client = $this->getBotClient();

    if (method_exists($client, $name)) {
      return call_user_func_array([$api, $name], $arguments);
    }
    else {
      return parent::__call($name, $arguments);
    }
  }

  /**
   * Gets webhook key.
   *
   * @todo Should we make this system dependent?
   */
  protected function getWebhookKey() {
    return !empty($this->id) && !empty($this->token) ? md5($this->id . ':' . $this->token) : NULL;
  }

}
