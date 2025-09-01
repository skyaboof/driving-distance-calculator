<?php

namespace Drupal\driving_distance_calculator\Carrier\Plugin;

use Drupal\driving_distance_calculator\Carrier\CarrierRateProviderBase;
use Drupal\driving_distance_calculator\Carrier\CarrierRateProviderInterface;
use Drupal\driving_distance_calculator\Carrier\Plugin\annotation\CarrierRate;

/**
 * @CarrierRate(
 *   id = "ups",
 *   label = @Translation("UPS")
 * )
 */
class UpsCarrier extends CarrierRateProviderBase implements CarrierRateProviderInterface {

  public function id(): string { return 'ups'; }

  public function isEnabled(): bool {
    $s = $this->settings()['ups'] ?? [];
    return !empty($s['enabled']) && !empty($s['access_key']) && !empty($s['username']) && !empty($s['password']);
  }

  public function quote(array $shipment): array {
    if (!$this->isEnabled()) return [];
    // NOTE: Real integration requires UPS OAuth/REST. This is a placeholder that returns a synthetic rate.
    $hash = substr(hash('sha256', json_encode($shipment)), 0, 6);
    return [
      ['carrier' => 'UPS', 'service' => 'Ground', 'amount' => 12.50, 'currency' => 'USD', 'meta' => ['debug' => "mock:$hash"]],
      ['carrier' => 'UPS', 'service' => '2nd Day Air', 'amount' => 24.30, 'currency' => 'USD', 'meta' => ['debug' => "mock:$hash"]],
    ];
  }
}
