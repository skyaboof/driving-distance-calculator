<?php

namespace Drupal\driving_distance_calculator\Pricing\Plugin\annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class PricingRule extends Plugin {
  public $id;
  public $label;
  public $weight = 0;
}
