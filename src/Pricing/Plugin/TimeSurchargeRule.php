<?php

namespace Drupal\driving_distance_calculator\Pricing\Plugin;

use Drupal\driving_distance_calculator\Pricing\PricingRuleInterface;
use Drupal\driving_distance_calculator\Pricing\Plugin\annotation\PricingRule;

/**
 * @PricingRule(
 *   id = "time_surcharge",
 *   label = @Translation("Time-of-Day Surcharge"),
 *   weight = 0
 * )
 */
class TimeSurchargeRule implements PricingRuleInterface {

  public function id(): string { return 'time_surcharge'; }

  public function apply(array $quotes, array $context): array {
    $surcharge = (float) ($context['peak_surcharge'] ?? 0.0);
    $isPeak = (bool) ($context['is_peak'] ?? false);
    if (!$isPeak || $surcharge <= 0) return $quotes;

    foreach ($quotes as &$q) {
      $q['amount'] = max(0, $q['amount'] + $surcharge);
      $q['meta']['time_surcharge'] = $surcharge;
    }
    return $quotes;
  }
}
