<?php

namespace Drupal\driving_distance_calculator\Pricing;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class PricingRuleManager extends DefaultPluginManager {
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Pricing/Plugin', $namespaces, $module_handler, PricingRuleInterface::class, 'Drupal\driving_distance_calculator\Pricing\Plugin\annotation\PricingRule');
    $this->alterInfo('driving_distance_calculator_pricing_info');
    $this->setCacheBackend($cache_backend, 'driving_distance_calculator_pricing');
  }
}
