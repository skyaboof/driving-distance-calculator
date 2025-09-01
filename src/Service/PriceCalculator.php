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
class PriceCalculator {

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
        // Merge breakdown items.
        if (!empty($result['breakdown'])) {
          $breakdown = array_merge($breakdown, $result['breakdown']);
        }
        break;

      case 'per_km':
      default:
        // Default legacy per_km mode (keeps previous behavior).
        $cost = $this->calculatePerKm($distance_km);
        $breakdown[] = ['label' => 'Distance-based fee', 'amount' => $cost];
        break;
    }

    // Apply a global priority multiplier if requested (configurable).
    if (!empty($options['priority']) && $options['priority'] === TRUE) {
      $priority_multiplier = (float) ($this->config->get('priority_multiplier') ?? 1.25);
      $before = $cost;
      $cost = $cost * $priority_multiplier;
      $breakdown[] = [
        'label' => sprintf('Priority multiplier (x%.2f)', $priority_multiplier),
        'amount' => round($cost - $before, 2),
      ];
    }

    // After-hours surcharge:
    if (!isset($options['requested_delivery_timestamp'])) {
      // If no requested delivery, we cannot apply time windows. skip.
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

    // Final fragile surcharge (if mode did not already include it).
    // In the distance_and_weight_tiered branch fragile may have been included,
    // but it's safe to apply a configured fragile surcharge if set.
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

    // Round and return final cost.
    $cost = round((float) $cost, 2);

    return [
      'cost' => $cost,
      'breakdown' => $breakdown,
    ];
  }

  /**
   * Legacy flat mode.
   *
   * @return float
   *   Flat fee.
   */
  protected function calculateFlat() : float {
    return (float) ($this->config->get('flat_fee') ?? 0.0);
  }

  /**
   * Legacy per-km mode.
   *
   * @param float $distance_km
   *   Distance in km.
   *
   * @return float
   *   Cost.
   */
  protected function calculatePerKm(float $distance_km) : float {
    $base = (float) ($this->config->get('base_fee') ?? 0.0);
    $per_km = (float) ($this->config->get('per_km_rate') ?? 1.0);
    return $base + ($distance_km * $per_km);
  }

  /**
   * Legacy per-minute mode.
   *
   * @param float $duration_minutes
   *   Duration in minutes.
   *
   * @return float
   *   Cost.
   */
  protected function calculatePerMinute(float $duration_minutes) : float {
    $base = (float) ($this->config->get('base_fee') ?? 0.0);
    $per_min = (float) ($this->config->get('per_minute_rate') ?? 0.5);
    return $base + ($duration_minutes * $per_min);
  }

  /**
   * Legacy hybrid (distance + duration).
   *
   * @param float $distance_km
   * @param float $duration_minutes
   *
   * @return float
   *   Cost.
   */
  protected function calculateHybrid(float $distance_km, float $duration_minutes) : float {
    $base = (float) ($this->config->get('base_fee') ?? 0.0);
    $per_km = (float) ($this->config->get('per_km_rate') ?? 0.5);
    $per_min = (float) ($this->config->get('per_minute_rate') ?? 0.3);
    return $base + ($distance_km * $per_km) + ($duration_minutes * $per_min);
  }

  /**
   * New advanced mode: distance + weight tiered pricing.
   *
   * Config expectations (all optional; sensible defaults used):
   *  - base_fee: float
   *  - distance_tiers: array of arrays with keys ['max_km', 'per_km_rate']
   *    Example: [
   *      ['max_km' => 50, 'per_km_rate' => 0.8],
   *      ['max_km' => 200, 'per_km_rate' => 0.5],
   *      ['max_km' => 999999, 'per_km_rate' => 0.35],
   *    ]
   *  - weight_tiers: array of arrays with keys ['max_kg', 'extra_per_km']
   *    Example: [
   *      ['max_kg' => 10, 'extra_per_km' => 0],
   *      ['max_kg' => 30, 'extra_per_km' => 0.2],
   *      ['max_kg' => 999999, 'extra_per_km' => 0.5],
   *    ]
   *  - fragile_surcharge_flat: float (optional)
   *  - fragile_surcharge_pct: float (optional)
   *  - priority_multiplier: float (optional)
   *
   * These config arrays may be stored as JSON in your settings form or as
   * structured form values — adjust the settings form to save them as arrays.
   *
   * @param float $distance_km
   * @param float $weight_kg
   * @param float $duration_minutes
   * @param bool $fragile
   * @param array $options
   *
   * @return array
   *   [ 'cost' => float, 'breakdown' => array ]
   */
  protected function calculateDistanceAndWeightTiered(float $distance_km, float $weight_kg, float $duration_minutes = 0.0, bool $fragile = FALSE, array $options = []) : array {
    $base = (float) ($this->config->get('base_fee') ?? 0.0);
    $breakdown = [];
    $cost = $base;
    if ($base > 0) {
      $breakdown[] = ['label' => 'Base fee', 'amount' => $base];
    }

    // Distance tiering: read config as array or JSON.
    $distance_tiers = $this->config->get('distance_tiers') ?? NULL;
    if (is_string($distance_tiers)) {
      $decoded = json_decode($distance_tiers, TRUE);
      if (is_array($decoded)) {
        $distance_tiers = $decoded;
      }
    }
    // Fallback default tiers if none configured.
    if (empty($distance_tiers) || !is_array($distance_tiers)) {
      $distance_tiers = [
        ['max_km' => 50, 'per_km_rate' => 1.0],
        ['max_km' => 200, 'per_km_rate' => 0.6],
        ['max_km' => 999999, 'per_km_rate' => 0.4],
      ];
    }

    // Weight tiers: read config as array or JSON.
    $weight_tiers = $this->config->get('weight_tiers') ?? NULL;
    if (is_string($weight_tiers)) {
      $decoded = json_decode($weight_tiers, TRUE);
      if (is_array($decoded)) {
        $weight_tiers = $decoded;
      }
    }
    if (empty($weight_tiers) || !is_array($weight_tiers)) {
      // Default: light: <=10kg no extra, medium: <=30kg +0.2/km, heavy: >30kg +0.5/km.
      $weight_tiers = [
        ['max_kg' => 10, 'extra_per_km' => 0.0],
        ['max_kg' => 30, 'extra_per_km' => 0.2],
        ['max_kg' => 999999, 'extra_per_km' => 0.5],
      ];
    }

    // Calculate distance-based cost using tiers. We assume the simplest model:
    // find the tier that matches the total distance and apply its per_km rate.
    $applied_distance_rate = $distance_tiers[count($distance_tiers) - 1]['per_km_rate'];
    foreach ($distance_tiers as $tier) {
      if ($distance_km <= (float) $tier['max_km']) {
        $applied_distance_rate = (float) $tier['per_km_rate'];
        break;
      }
    }
    $distance_cost = $distance_km * $applied_distance_rate;
    $cost += $distance_cost;
    $breakdown[] = ['label' => sprintf('Distance (%.2f km @ %s/km)', $distance_km, $this->formatMoney($applied_distance_rate)), 'amount' => round($distance_cost, 2)];

    // Weight surcharge per-km according to weight tier.
    $applied_extra_per_km = $weight_tiers[count($weight_tiers) - 1]['extra_per_km'];
    foreach ($weight_tiers as $wt) {
      if ($weight_kg <= (float) $wt['max_kg']) {
        $applied_extra_per_km = (float) $wt['extra_per_km'];
        break;
      }
    }
    if ($applied_extra_per_km > 0 && $weight_kg > 0) {
      $weight_surcharge = $distance_km * $applied_extra_per_km;
      $cost += $weight_surcharge;
      $breakdown[] = ['label' => sprintf('Weight surcharge (%.2f kg @ %s/km)', $weight_kg, $this->formatMoney($applied_extra_per_km)), 'amount' => round($weight_surcharge, 2)];
    }

    // Optionally include time-based cost (if configured to combine with duration).
    $include_duration = (bool) ($this->config->get('distance_tier_include_duration') ?? FALSE);
    if ($include_duration && $duration_minutes > 0) {
      $per_min = (float) ($this->config->get('per_minute_rate') ?? 0.3);
      $duration_cost = $duration_minutes * $per_min;
      $cost += $duration_cost;
      $breakdown[] = ['label' => sprintf('Duration (%.0f min @ %s/min)', $duration_minutes, $this->formatMoney($per_min)), 'amount' => round($duration_cost, 2)];
    }

    // Fragile handling: either handled here or later via global fragile config.
    // If config indicates fragile is applied inside this mode, apply.
    $mode_fragile_handling = $this->config->get('tiered_fragile_included') ?? FALSE;
    if ($fragile && $mode_fragile_handling) {
      $fragile_flat = (float) ($this->config->get('tiered_fragile_surcharge_flat') ?? 0.0);
      $fragile_pct = (float) ($this->config->get('tiered_fragile_surcharge_pct') ?? 0.0);
      $add = $fragile_flat + ($fragile_pct > 0 ? ($cost * ($fragile_pct / 100)) : 0);
      $cost += $add;
      $breakdown[] = ['label' => sprintf('Fragile surcharge (mode) %s', $fragile_flat ? $this->formatMoney($fragile_flat) : sprintf('(%.1f%%)', $fragile_pct)), 'amount' => round($add, 2)];
    }

    return [
      'cost' => $cost,
      'breakdown' => $breakdown,
    ];
  }

  /**
   * Determine if a timestamp is after business hours / weekend.
   *
   * Use config 'business_hours' if you store it, otherwise default to:
   *  Mon-Fri 08:00 - 18:00 local server time.
   *
   * @param int $timestamp
   *   Unix timestamp.
   *
   * @return bool
   *   TRUE if after-hours or weekend.
   */
  protected function isAfterHours(int $timestamp) : bool {
    // Default business hours: Monday-Friday 8:00 - 18:00.
    $weekday = (int) gmdate('w', $timestamp); // 0 (Sun) - 6 (Sat)
    $hour = (int) gmdate('G', $timestamp); // 0-23
    // Treat server time as UTC here; if you store timezone in config, convert.
    // Weekend:
    if ($weekday === 0 || $weekday === 6) {
      return TRUE;
    }
    // Business hours:
    $start = (int) ($this->config->get('business_start_hour') ?? 8);
    $end = (int) ($this->config->get('business_end_hour') ?? 18);
    if ($hour < $start || $hour >= $end) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Format money for labels.
   *
   * @param float $value
   *   Value.
   *
   * @return string
   *   String like '$1.23' — uses currency from config or defaults to $.
   */
  protected function formatMoney(float $value) : string {
    $currency = $this->config->get('currency_symbol') ?? '$';
    return $currency . number_format((float) $value, 2, '.', '');
  }

}
