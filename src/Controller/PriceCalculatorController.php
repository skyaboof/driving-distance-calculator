<?php

namespace Drupal\driving_distance_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\driving_distance_calculator\Service\PriceCalculatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller exposing JSON price calculation.
 */
class PriceCalculatorController extends ControllerBase {

  protected PriceCalculatorInterface $calculator;
  protected RequestStack $requestStack;

  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->calculator = $container->get('driving_distance_calculator.price_calculator');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  public function calculate() {
    $request = $this->requestStack->getCurrentRequest();

    $distance = (float) $request->query->get('distance', 0);
    $minutes  = (float) $request->query->get('minutes', 0);
    $weight   = (float) $request->query->get('weight', 0);
    $fragile  = $request->query->get('fragile', 0);
    $fragile  = in_array($fragile, ['1', 1, true, 'true'], true) ? 1 : 0;

    // Only pass user-entered variables; pricing config is read from service.
    $result = $this->calculator->calculate($distance, $minutes, [
      'weight'  => $weight,
      'fragile' => $fragile,
    ]);

    $response = new JsonResponse($result);
    $response->headers->set('Cache-Control', 'no-store');
    $response->setPrivate();
    $response->setMaxAge(0);
    $response->setImmutable(false);

    if (($result['status'] ?? '') === 'error') {
      $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    }

    return $response;
  }

}
