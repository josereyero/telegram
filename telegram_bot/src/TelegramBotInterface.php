<?php

namespace Drupal\telegram_bot;

/**
 * Interface for TelegramBot Plugins
 */
interface TelegramBotInterface {

  /**
   * Invoke cron
   */
  public function invokeCron();

  /**
   * Process webhook callback.
   */
  public function invokeWebhook();

  /**
   * Validate Webhook token.
   */
  public function validateKey($token);


}