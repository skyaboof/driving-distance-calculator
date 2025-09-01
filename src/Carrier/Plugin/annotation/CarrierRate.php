<?php

namespace Drupal\driving_distance_calculator\Carrier\Plugin\annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class CarrierRate extends Plugin {
  public $id;
  public $label;
}
