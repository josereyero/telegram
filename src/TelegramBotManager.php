<?php

namespace Drupal\telegram;

use Psr\Log\LoggerInterface;
use Drupal\telegram\TelegramSettings;

use Drupal\service_container\Plugin\DefaultPluginManager;
use Drupal\service_container_annotation_discovery\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Telegram Bot Manager
 */
class TelegramBotManager extends DefaultPluginManager {

  /**
   * Bot information for all bots in the system
   *
   * @var array
   */
  protected $info;

  /**
   * Running bots
   *
   * @var TelegramBotInterface[]
   */
  protected $bots;

  /**
   * Settings to pass to the process.
   *
   * @var Drupal\telegram\TelegramSettings
   */
  protected $settings;

  /**
   * The Telegram logger
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Class constructor.
   *
   * @var TelegramSettings $settings
   *   Telegram settings.
   * @var \Psr\Log\LoggerInterface $logger
   *   The Telegram Logger.
   */
  public function __construct(TelegramSettings $settings, LoggerInterface $logger) {
    parent::__construct(new AnnotatedClassDiscovery([
      'directory' => 'Plugin/TelegramBot',
      'class' => 'Drupal\telegram\Annotation\TelegramBot'
    ]));
    $this->settings = $settings;
    $this->logger = $logger;
  }

  /**
   * Get / create bot.
   *
   * @param string $id
   *   The bot id.
   *
   * @throws PluginNotFoundException
   */
  public function getTelegramBot($id) {
    if (!isset($this->bots[$id])) {
      if ($definition = $this->getDefinition($id)) {
        //$class = $definition['class'];
        //$this->bots[$id] = $class::create($definition);
        $configuration = [
          'logger' => $this->logger,
          'settings' => $this->settings,
        ];
        $this->bots[$id] = $this->createInstance($id, $configuration);
      }
    }
    return $this->bots[$id];
  }

  /**
   * Check whether a specific bot is enabled.
   */
  public function isBotEnabled($id) {
    return $this->settings->get($id . '_enable');
  }

  /**
   * Gets defined bots information.
   *
   * @return array
   */
  protected function getBotInfo() {
    if (!isset($this->info)) {
      $this->info = telegram_bot_info();
    }
    return $this->info;
  }

}
