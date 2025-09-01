<?php

namespace Drupal\driving_distance_calculator\Service;

/**
 * Interface for the driving distance price calculator.
 */
interface PriceCalculatorInterface {

  /**
   * Calculate price.
   *
   * @param float $distance_km
   *   Driving distance in kilometers.
   * @param float $duration_minutes
   *   Driving duration in minutes (optional).
   * @param float $weight_kg
   *   Shipment weight in kilograms (optional).
   * @param bool $fragile
   *   Whether the shipment is fragile (optional).
   * @param array $options
   *   Optional extra data:
   *     - 'priority' => bool
   *     - 'requested_delivery_timestamp' => int (unix timestamp)
   *
   * @return array
   *   [
   *     'cost' => (float) total_price,
   *     'breakdown' => (array) detailed breakdown for display/logging,
   *   ]
   */
  public function calculate(float $distance_km, float $duration_minutes = 0.0, float $weight_kg = 0.0, bool $fragile = FALSE, array $options = []) : array;

}
