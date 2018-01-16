<?php

namespace Drupal\telegram\TelegramBot;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @see https://core.telegram.org/bots/api#setwebhook
 */
interface TelegramBotCronInterface {

  /**
   * Invoke cron
   */
  public function invokeCron();
}