<?php

namespace Drupal\telegram_bot;

use Psr\Log\LoggerInterface;
use Drupal\telegram\TelegramSettings;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;

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
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, TelegramSettings $settings, LoggerInterface $logger) {
    parent::__construct(
      'Plugin/TelegramBot',
      $namespaces,
      $module_handler,
      'Drupal\telegram_bot\TelegramBotInterface', //NULL, // @todo Interface
      'Drupal\telegram_bot\Annotation\TelegramBot'
    );

    //$this->alterInfo('telegram_bot');
    $this->setCacheBackend($cache_backend, 'telegram_bot');
    $this->factory = new DefaultFactory($this->getDiscovery());

    $this->settings = $settings;
    $this->logger = $logger;
  }

  /**
   * Debug: getDiscovery.
   */
  public function getDiscovery() {
    return parent::getDiscovery();
  }

  /**
   * Debug: Finds plugin definitions.
   */
  public function findDefinitions() {
    return parent::findDefinitions();
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
