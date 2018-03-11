<?php

namespace Drupal\telegram_bot;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @see https://core.telegram.org/bots/api#setwebhook
 */
interface TelegramBotCronInterface extends TelegramBotInterface {

  /**
   * Invoke cron
   */
  public function invokeCron();
}