<?php

namespace Drupal\driving_distance_calculator\Carrier;

interface CarrierRateProviderInterface {
  /**
   * @param array $shipment ['origin'=>..., 'destination'=>..., 'packages'=>[ ['weight'=>, 'length'=>, 'width'=>, 'height'=>], ... ]]
   * @return array List of services: [['carrier'=>'UPS','service'=>'Ground','amount'=>12.34,'currency'=>'USD','meta'=>[]], ...]
   */
  public function quote(array $shipment): array;

  /**
   * True if this provider is enabled/configured.
   */
  public function isEnabled(): bool;

  /**
   * Unique provider ID (e.g., 'ups', 'fedex', 'usps').
   */
  public function id(): string;
}
