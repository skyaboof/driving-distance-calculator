<?php

namespace Drupal\driving_distance_calculator\Service;

class PricingCalculator {

  /**
   * Compute pricing based on form data.
   * Returns array with keys: total (float), distance_m (int), base (float), details (array of strings)
   */
  public function compute(array $data) {
    $total = 0.0;
    $base = 0.0;
    $details = [];

    $service = isset($data['service_type']) ? $data['service_type'] : NULL;

    // Helper to check checkbox arrays
    $has = function($key, $value) use ($data) {
      if (empty($data[$key])) {
        return FALSE;
      }
      if (is_array($data[$key])) {
        return in_array($value, $data[$key]);
      }
      return $data[$key] == $value;
    };

    if ($service === 'long_distance_move') {
      $size = isset($data['shipment_home_size']) ? $data['shipment_home_size'] : NULL;
      if (in_array($size, ['studio','1_br'])) {
        $base = 1500;
        $details[] = 'Long-distance moves start from $1,500.';
      }
      elseif ($size === '2_br') {
        $base = 3000;
        $details[] = 'Long-distance moves start from $3,000.';
      }
      elseif (in_array($size, ['3_br','4_br_plus'])) {
        $base = 5000;
        $details[] = 'Long-distance moves start from $5,000.';
      }
      else {
        $details[] = 'Please select a move size to see a price estimate. For distances under 1,000 miles, the cost can range from $0.50 to $0.80 per pound.';
      }
    }
    elseif ($service === 'local_residential_move') {
      $size = isset($data['move_size_residential']) ? $data['move_size_residential'] : NULL;
      switch ($size) {
        case 'studio': $base = 415; break;
        case '1_br': $base = 569; break;
        case '2_br': $base = 909; break;
        case '3_br':
        case '4_br_plus': $base = 2073; break;
        default: $base = 0; break;
      }

      // Access conditions adjustments
      if (!empty($data['origin_access_conditions'])) {
        if ($data['origin_access_conditions'] === 'stairs' && !empty($data['origin_stairs_flights'])) {
          $base += ($data['origin_stairs_flights'] * 17.5);
        }
        elseif ($data['origin_access_conditions'] === 'passenger_elevator') {
          $base += 35;
        }
        elseif ($data['origin_access_conditions'] === 'freight_elevator') {
          $base += 20;
        }
      }
      if (!empty($data['destination_access_conditions'])) {
        if ($data['destination_access_conditions'] === 'stairs' && !empty($data['destination_stairs_flights'])) {
          $base += ($data['destination_stairs_flights'] * 17.5);
        }
        elseif ($data['destination_access_conditions'] === 'passenger_elevator') {
          $base += 35;
        }
        elseif ($data['destination_access_conditions'] === 'freight_elevator') {
          $base += 20;
        }
      }

      if (!empty($data['parking_situation'])) {
        if ($data['parking_situation'] === 'long_carry') {
          $base += 75;
        }
        elseif ($data['parking_situation'] === 'shuttle') {
          $base += 250;
        }
      }

      if ($base > 0) {
        $details[] = 'The estimated cost for your local move is around $' . number_format($base, 0, '.', ',') . '.';
      }
      else {
        $details[] = 'Please select a move size to see a price estimate. Local movers in NYC typically charge an average of $110 per hour.';
      }
    }
    elseif ($service === 'local_commercial_move') {
      $base = 300;
      if (!empty($data['additional_services_commercial'])) {
        if ($has('additional_services_commercial','packing_unpacking')) {
          $base += 130;
        }
        if ($has('additional_services_commercial','it_services')) {
          $base += 200;
        }
        if ($has('additional_services_commercial','furniture_assembly')) {
          $base += 150;
        }
      }
      $details[] = 'The estimated cost for your commercial move is around $' . number_format($base,0,'.',',') . '.';
    }
    elseif ($service === 'specialty_item_move') {
      $item = isset($data['item_to_be_moved']) ? $data['item_to_be_moved'] : NULL;
      switch ($item) {
        case 'piano': $base = 425; $details[] = 'The price for moving a piano is around $425. Final cost depends on factors like size and access.'; break;
        case 'gun_safe': $base = 275; $details[] = 'The estimated cost for moving a gun safe is around $275.'; break;
        case 'fine_art': $base = 350; $details[] = 'The estimated cost for moving fine art is around $350. A final quote requires a detailed assessment.'; break;
        case 'gym_equipment': $base = 225; $details[] = 'The estimated cost for moving heavy gym equipment is around $225 per item.'; break;
        case 'other': $base = 100; $details[] = 'The estimated cost for moving a single item is around $100.'; break;
        default: $details[] = 'Please select an item type to see a price estimate.'; break;
      }
    }
    elseif ($service === 'on_demand_delivery') {
      $base_cost = 0;
      if (!empty($data['vehicle_helper_needs'])) {
        if ($data['vehicle_helper_needs'] === 'van_1_helper') { $base_cost = 200; }
        elseif ($data['vehicle_helper_needs'] === 'truck_1_helper') { $base_cost = 300; }
        elseif ($data['vehicle_helper_needs'] === 'truck_2_helpers') { $base_cost = 475; }
      }
      $delivery_speed_cost = 0;
      if (!empty($data['service_speed'])) {
        if ($data['service_speed'] === 'rush') { $delivery_speed_cost = 75; }
        elseif ($data['service_speed'] === 'expedited') { $delivery_speed_cost = 150; }
      }

      $base = $base_cost + $delivery_speed_cost;
      if ($base > 0) {
        $details[] = 'The estimated base cost for your delivery is $' . number_format($base_cost,0,'.',',') . '.';
        if ($delivery_speed_cost > 0) {
          $details[] = 'An additional $' . number_format($delivery_speed_cost,0,'.',',') . ' is added for your selected delivery speed.';
        }
        $details[] = 'The total estimated cost for your on-demand delivery is around $' . number_format($base,0,'.',',') . '.';
      }
      else {
        $details[] = 'Please select your vehicle, helper needs and service speed to see a price estimate.';
      }
    }
    else {
      $details[] = "We'll call you soon.";
    }

    // Add optional add-ons
    if (!empty($data['packing_services'])) {
      $base += 325;
      $details[] = 'Professional Packing Services: +$325 (estimated average)';
    }
    if (!empty($data['cleaning_services'])) {
      $base += 210;
      $details[] = 'Move-In / Move-Out Cleaning: +$210 (estimated average)';
    }

    // Fake distance when both addresses are present (allow callers to pass in distance_m if available)
    $distance_m = 0;
    if (!empty($data['distance_m'])) {
      $distance_m = (int) $data['distance_m'];
    }
    elseif (!empty($data['origin_address']) && !empty($data['destination_address'])) {
      // placeholder distance
      $distance_m = 5000;
    }

    $total = $base + ($distance_m / 1000.0) * 1.2;

    return [
      'total' => round($total, 2),
      'distance_m' => $distance_m,
      'base' => round($base, 2),
      'details' => $details,
    ];
  }
}
