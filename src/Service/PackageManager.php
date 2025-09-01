<?php

namespace Drupal\driving_distance_calculator\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Basic package management / packing helper.
 *
 * Minimal placeholder — extend later for multi-package packing algorithms.
 */
class PackageManager {

  protected $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Calculate dimensional weight (simple wrapper).
   */
  public function calculateDimWeight(float $l, float $w, float $h): float {
    $divisor = (int) ($this->configFactory->get('driving_distance_calculator.settings')->get('dim_divisor') ?? 5000);
    if ($divisor <= 0) {
      $divisor = 5000;
    }
    if ($l <= 0 || $w <= 0 || $h <= 0) {
      return 0.0;
    }
    return round(($l * $w * $h) / $divisor, 2);
  }

  /**
   * Return billable weight.
   */
  public function billableWeight(float $actual, float $dim): float {
    return max(0.0, max($actual, $dim));
  }

  /**
   * Very small extras calculator (expand as required).
   */
  public function calculateExtras(array $options = []): float {
    $total = 0.0;
    if (!empty($options['fragile'])) {
      $total += 10.0;
    }
    if (!empty($options['stairs'])) {
      $total += (int) $options['stairs'] * 10.0;
    }
    return $total;
  }

}
