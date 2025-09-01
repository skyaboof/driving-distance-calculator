<?php

namespace Drupal\driving_distance_calculator\Pricing\Plugin;

use Drupal\driving_distance_calculator\Pricing\PricingRuleInterface;
use Drupal\driving_distance_calculator\Pricing\Plugin\annotation\PricingRule;

/**
 * @PricingRule(
 *   id = "zone_base",
 *   label = @Translation("Zone Base Price"),
 *   weight = -10
 * )
 */
class ZoneBaseRule implements PricingRuleInterface {

  public function id(): string { return 'zone_base'; }

  public function apply(array $quotes, array $context): array {
    $zoneBase = (float) ($context['zone_base'] ?? 0.0);
    if ($zoneBase <= 0) return $quotes;

    foreach ($quotes as &$q) {
      $q['amount'] = max(0, $q['amount'] + $zoneBase);
      $q['meta']['zone_base_applied'] = $zoneBase;
    }
    return $quotes;
  }
}
