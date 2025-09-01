<?php

namespace Drupal\driving_distance_calculator\Carrier\Plugin;

use Drupal\driving_distance_calculator\Carrier\CarrierRateProviderBase;
use Drupal\driving_distance_calculator\Carrier\CarrierRateProviderInterface;
use Drupal\driving_distance_calculator\Carrier\Plugin\annotation\CarrierRate;

/**
 * @CarrierRate(
 *   id = "fedex",
 *   label = @Translation("FedEx")
 * )
 */
class FedExCarrier extends CarrierRateProviderBase implements CarrierRateProviderInterface {

  public function id(): string { return 'fedex'; }

  public function isEnabled(): bool {
    $s = $this->settings()['fedex'] ?? [];
    return !empty($s['enabled']) && !empty($s['key']) && !empty($s['password']) && !empty($s['account_number']) && !empty($s['meter_number']);
  }

  public function quote(array $shipment): array {
    if (!$this->isEnabled()) return [];
    $hash = substr(hash('sha256', json_encode($shipment)), 0, 6);
    return [
      ['carrier' => 'FedEx', 'service' => 'Ground', 'amount' => 13.10, 'currency' => 'USD', 'meta' => ['debug' => "mock:$hash"]],
      ['carrier' => 'FedEx', 'service' => 'Overnight', 'amount' => 42.00, 'currency' => 'USD', 'meta' => ['debug' => "mock:$hash"]],
    ];
  }
}
