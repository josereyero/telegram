<?php

namespace Drupal\telegram;

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
interface TelegramBotInterface {

  /**
   * Bot type
   */
  const TYPE_CRON = 'cron';
  const TYPE_WEBHOOK = 'webhook';

  /**
   * Create bot.
   *
   * @param array $info
   *
   * @param array $settings
   */
  public static function create($bot_id, $definition);

  /**
   * Invoke cron on boot.
   *
   * @see telegram_bot_cron().
   */
  public static function cron();


}
