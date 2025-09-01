<?php

declare(strict_types=1);

namespace Drupal\driving_distance_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\driving_distance_calculator\Service\DistanceService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for AJAX/dedicated endpoint to calculate distance.
 */
class DistanceController extends ControllerBase {

  protected DistanceService $distanceService;

  public function __construct(DistanceService $distance_service) {
    $this->distanceService = $distance_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('driving_distance_calculator.distance_service')
    );
  }

  /**
   * Simple endpoint to calculate distance.
   *
   * Expects query parameters: from (lon,lat) and to (lon,lat) as comma-separated.
   *
   * Example: /driving-distance/calculate?from=-122.42,37.78&to=-122.45,37.91
   */
  public function calculate(Request $request) {
    $from_raw = $request->query->get('from');
    $to_raw = $request->query->get('to');

    if (empty($from_raw) || empty($to_raw)) {
      return new JsonResponse(['error' => 'Missing "from" or "to" parameters'], 400);
    }

    $from = array_map('floatval', explode(',', $from_raw));
    $to = array_map('floatval', explode(',', $to_raw));

    if (count($from) < 2 || count($to) < 2) {
      return new JsonResponse(['error' => 'Invalid "from" or "to" coordinates. Expected lon,lat.'], 400);
    }

    $result = $this->distanceService->calculateDistance($from, $to);

    return new JsonResponse($result);
  }

}
