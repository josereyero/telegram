<?php

namespace Drupal\telegram_bot_test\Plugin\TelegramBot;

use Drupal\telegram_bot\Plugin\TelegramBot\TelegramBotBase;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\Message;

/**
 * Telegram Bot object implementing the Bot API.
 *
 * @TelegramBot(
 *   id = "test_cron",
 *   label = "Test Cron",
 *   description = "Test bot running on cron that says hello every time.",
 * )
 */
class TelegramCronTestBot extends TelegramBotBase {

  /**
   * {@inheritdoc}
   */
  public function invokeCron() {
    parent::invokeCron();
    $this->sayHelloToUsers();
  }

  /**
   * Say hello to users in every chat.
   */
  public function sayHelloToUsers() {
    foreach ($this->getObjects(['type' => 'chat']) as $chat) {
      $this->getBotApi()->sendMessage($chat->getId(), t("Hello from @name!", ['@name' => $this->pluginDefinition['label']]));
    }
  }

  /**
   * Process update.
   *
   * @param \TelegramBot\Api\Types\Message $message
   */
  protected function processMessage(Message $message) {
    if ($chat = $message->getChat()) {
      $this->saveObject($chat);
    }
    if ($user = $message->getFrom()) {
      $this->saveObject($user);
    }
  }

}
