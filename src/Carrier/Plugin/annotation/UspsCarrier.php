<?php

namespace Drupal\driving_distance_calculator\Carrier\Plugin;

use Drupal\driving_distance_calculator\Carrier\CarrierRateProviderBase;
use Drupal\driving_distance_calculator\Carrier\CarrierRateProviderInterface;
use Drupal\driving_distance_calculator\Carrier\Plugin\annotation\CarrierRate;

/**
 * @CarrierRate(
 *   id = "usps",
 *   label = @Translation("USPS")
 * )
 */
class UspsCarrier extends CarrierRateProviderBase implements CarrierRateProviderInterface {

  public function id(): string { return 'usps'; }

  public function isEnabled(): bool {
    $s = $this->settings()['usps'] ?? [];
    return !empty($s['enabled']) && !empty($s['userid']);
  }

  public function quote(array $shipment): array {
    if (!$this->isEnabled()) return [];
    $hash = substr(hash('sha256', json_encode($shipment)), 0, 6);
    return [
      ['carrier' => 'USPS', 'service' => 'Priority Mail', 'amount' => 11.80, 'currency' => 'USD', 'meta' => ['debug' => "mock:$hash"]],
      ['carrier' => 'USPS', 'service' => 'Express', 'amount' => 31.25, 'currency' => 'USD', 'meta' => ['debug' => "mock:$hash"]],
    ];
  }
}
