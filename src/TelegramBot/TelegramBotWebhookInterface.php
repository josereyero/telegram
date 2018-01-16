<?php

namespace Drupal\telegram\TelegramBot;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @see https://core.telegram.org/bots/api#setwebhook
 */
interface TelegramBotWebhookInterface {

  /**
   * Validate Webhook token.
   */
  public function validateKey($token);

  /**
   * Process webhook callback.
   */
  public function invokeWebhook();
}