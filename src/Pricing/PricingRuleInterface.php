<?php

namespace Drupal\driving_distance_calculator\Pricing;

interface PricingRuleInterface {
  /**
   * Mutate/augment an array of carrier quotes (in-place or by return).
   *
   * @param array $quotes Each: ['carrier','service','amount','currency','meta'=>[]]
   * @param array $context Arbitrary context (date/time, zones, flags).
   * @return array Adjusted quotes.
   */
  public function apply(array $quotes, array $context): array;

  public function id(): string;
}
