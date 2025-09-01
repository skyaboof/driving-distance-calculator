<?php

namespace Drupal\driving_distance_calculator\Carrier;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class CarrierRateProviderManager extends DefaultPluginManager {
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Carrier/Plugin', $namespaces, $module_handler, CarrierRateProviderInterface::class, 'Drupal\driving_distance_calculator\Carrier\Plugin\annotation\CarrierRate');
    $this->alterInfo('driving_distance_calculator_carrier_info');
    $this->setCacheBackend($cache_backend, 'driving_distance_calculator_carriers');
  }
}
