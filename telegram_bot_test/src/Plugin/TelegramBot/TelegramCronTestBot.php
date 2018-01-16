<?php

namespace Drupal\telegram_bot_test\Plugin\TelegramBot;

use Drupal\telegram\Plugin\TelegramBot\TelegramCronBot;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @TelegramBot(
 *   id = "test_cron",
 *   label = "Test Cron",
 *   description = "Test bot running on cron",
 * )
 */
class TelegramCronTestBot extends TelegramCronBot {

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

}
