<?php

namespace Drupal\driving_distance_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PriceCalcController extends ControllerBase {

  protected $calculator;

  public function __construct() {
    $this->calculator = \\Drupal::service('driving_distance_calculator.pricing_calculator');
  }

  public function calculate(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    if (!is_array($content)) {
      $content = [];
    }

    $result = $this->calculator->compute($content);

    return new JsonResponse($result);
  }
}
