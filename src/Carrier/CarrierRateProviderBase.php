<?php

namespace Drupal\driving_distance_calculator\Carrier;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\driving_distance_calculator\Service\CarrierHttpClient;

abstract class CarrierRateProviderBase implements CarrierRateProviderInterface {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected CarrierHttpClient $client
  ) {}

  protected function settings(): array {
    return (array) $this->configFactory->get('driving_distance_calculator.settings')->get('carriers') ?: [];
  }

}
