<?php

namespace Drupal\driving_distance_calculator\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service to calculate dimensional and billable weight.
 */
class DimensionalWeightCalculator {

  protected $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Calculate dimensional weight in kg.
   */
  public function calculateDimensionalWeight(float $length_cm, float $width_cm, float $height_cm): float {
    $config = $this->configFactory->get('driving_distance_calculator.settings');
    $divisor = (int) ($config->get('dim_divisor') ?? 5000);
    if ($divisor <= 0) {
      $divisor = 5000;
    }
    if ($length_cm <= 0 || $width_cm <= 0 || $height_cm <= 0) {
      return 0.0;
    }
    $dim = ($length_cm * $width_cm * $height_cm) / $divisor;
    return round($dim, 2);
  }

  /**
   * Return billable weight (max(actual, dimensional)).
   */
  public function getBillableWeight(float $actual_kg, float $length_cm = 0, float $width_cm = 0, float $height_cm = 0): float {
    $dim = $this->calculateDimensionalWeight($length_cm, $width_cm, $height_cm);
    return max($actual_kg, $dim);
  }

}
