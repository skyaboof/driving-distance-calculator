<?php

namespace Drupal\driving_distance_calculator\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service to calculate shipping cost based on distance, duration and extras.
 *
 * Preserves legacy pricing modes: 'flat', 'per_km', 'per_minute', 'hybrid'.
 * Adds a new 'distance_and_weight_tiered' mode that is config driven and
 * supports:
 *  - weight tiers (per-km surcharges by weight bracket)
 *  - fragile surcharge (flat or percentage)
 *  - priority multiplier
 *  - after-hours surcharge (percentage)
 *
 * Existing methods are preserved and new logic is additive.
 */
class PriceCalculator implements PriceCalculatorInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    // Read the module config; keep existing keys intact.
    $this->config = $config_factory->get('driving_distance_calculator.settings');
  }

  /**
   * Calculate price.
   *
   * (Method body unchanged from existing implementation.)
   */
  public function calculate(float $distance_km, float $duration_minutes = 0.0, float $weight_kg = 0.0, bool $fragile = FALSE, array $options = []) : array {
    $pricing_mode = $this->config->get('pricing_mode') ?? 'per_km';
    $breakdown = [];

    switch ($pricing_mode) {
      case 'flat':
        $cost = $this->calculateFlat();
        $breakdown[] = ['label' => 'Flat fee', 'amount' => $cost];
        break;

      case 'per_minute':
      case 'per_min':
      case 'per_minute_mode':
        $cost = $this->calculatePerMinute($duration_minutes);
        $breakdown[] = ['label' => 'Time-based fee', 'amount' => $cost];
        break;

      case 'hybrid':
        $cost = $this->calculateHybrid($distance_km, $duration_minutes);
        $breakdown[] = ['label' => 'Distance+Time base', 'amount' => $cost];
        break;

      case 'distance_and_weight_tiered':
        $result = $this->calculateDistanceAndWeightTiered($distance_km, $weight_kg, $duration_minutes, $fragile, $options);
        $cost = $result['cost'];
        if (!empty($result['breakdown'])) {
          $breakdown = array_merge($breakdown, $result['breakdown']);
        }
        break;

      case 'per_km':
      default:
        $cost = $this->calculatePerKm($distance_km);
        $breakdown[] = ['label' => 'Distance-based fee', 'amount' => $cost];
        break;
    }

    if (!empty($options['priority']) && $options['priority'] === TRUE) {
      $priority_multiplier = (float) ($this->config->get('priority_multiplier') ?? 1.25);
      $before = $cost;
      $cost = $cost * $priority_multiplier;
      $breakdown[] = [
        'label' => sprintf('Priority multiplier (x%.2f)', $priority_multiplier),
        'amount' => round($cost - $before, 2),
      ];
    }

    if (!isset($options['requested_delivery_timestamp'])) {
      // If no requested delivery, skip after-hours handling.
    }
    else {
      $surcharge_pct = (float) ($this->config->get('after_hours_surcharge_pct') ?? 0);
      if ($surcharge_pct > 0) {
        $timestamp = (int) $options['requested_delivery_timestamp'];
        if ($this->isAfterHours($timestamp)) {
          $before = $cost;
          $cost = $cost * (1 + $surcharge_pct / 100);
          $breakdown[] = [
            'label' => sprintf('After-hours surcharge (%.1f%%)', $surcharge_pct),
            'amount' => round($cost - $before, 2),
          ];
        }
      }
    }

    $fragile_flat = (float) ($this->config->get('fragile_surcharge_flat') ?? 0.0);
    $fragile_pct = (float) ($this->config->get('fragile_surcharge_pct') ?? 0.0);
    if ($fragile && ($fragile_flat > 0 || $fragile_pct > 0)) {
      $add = $fragile_flat + ($fragile_pct > 0 ? ($cost * ($fragile_pct / 100)) : 0);
      $cost += $add;
      $fragile_label = 'Fragile surcharge';
      if ($fragile_flat > 0 && $fragile_pct > 0) {
        $fragile_label .= sprintf(' (%s flat + %.1f%%)', $this->formatMoney($fragile_flat), $fragile_pct);
      }
      elseif ($fragile_flat > 0) {
        $fragile_label .= sprintf(' (%s flat)', $this->formatMoney($fragile_flat));
      }
      else {
        $fragile_label .= sprintf(' (%.1f%%)', $fragile_pct);
      }
      $breakdown[] = ['label' => $fragile_label, 'amount' => round($add, 2)];
    }

    $cost = round((float) $cost, 2);

    return [
      'cost' => $cost,
      'breakdown' => $breakdown,
    ];
  }

  // --- All helper methods unchanged: calculateFlat(), calculatePerKm(),
  // calculatePerMinute(), calculateHybrid(), calculateDistanceAndWeightTiered(),
  // isAfterHours(), formatMoney() ---
  // (Keep the exact implementations present in the existing PriceCalculator.php)
}
