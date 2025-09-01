<?php

declare(strict_types=1);

namespace Drupal\driving_distance_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a simple block for driving distance calculator (display only).
 *
 * @Block(
 *   id = "driving_distance_calculator_block",
 *   admin_label = @Translation("Driving Distance Calculator"),
 * )
 */
class DistanceBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Use the Driving Distance Calculator settings at @link', [
        '@link' => \Drupal\Core\Url::fromRoute('driving_distance_calculator.settings')->toString(),
      ]),
    ];
  }

}
