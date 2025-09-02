<?php

namespace Drupal\driving_distance_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Simple price calculation endpoint (stub).
 */
class PriceCalcController extends ControllerBase {

  /**
   * Accepts JSON POST and returns a JSON price response.
   */
  public function calculate(Request $request) {
    $content = json_decode($request->getContent(), TRUE);

    // Basic stub: return a sample response. Replace with real logic.
    $base = 100;
    if (!empty($content['move_size_residential'])) {
      switch ($content['move_size_residential']) {
        case 'studio':
          $base = 100;
          break;
        case '1_bed':
          $base = 150;
          break;
        case '2_bed':
          $base = 225;
          break;
        case '3_bed':
          $base = 300;
          break;
        default:
          $base = 150;
      }
    }

    // Fake distance when both addresses are present (replace with geocode/OSRM/etc).
    $distance_m = 0;
    if (!empty($content['origin_address']) && !empty($content['destination_address'])) {
      $distance_m = 5000;
    }

    $total = $base + ($distance_m / 1000) * 1.2; // simple per-km rate
    $response = [
      'total' => round($total, 2),
      'distance_m' => $distance_m,
      'base' => $base,
    ];

    return new JsonResponse($response);
  }

}