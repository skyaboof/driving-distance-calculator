<?php

namespace Drupal\driving_distance_calculator\Service;

/**
 * Interface for price calculation services.
 */
interface PriceCalculatorInterface {
  /**
   * Calculate price based on distance, time and optional conditions.
   *
   * @param float $distance
   *   Distance in km.
   * @param float $minutes
   *   Time in minutes.
   * @param array $conditions
   *   Additional conditions (weight, fragile, pricing settings).
   *
   * @return array
   *   An array with either:
   *   - ['status' => 'success', 'total_price' => float, 'surcharge' => float, 'conditions' => array]
   *   - ['status' => 'error', 'errors' => array]
   */
  public function calculate(float $distance, float $minutes, array $conditions = []): array;
}
