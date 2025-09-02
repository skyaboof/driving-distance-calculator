<?php

namespace Drupal\driving_distance_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a "Moving Quote" Block.
 *
 * @Block(
 *   id = "moving_quote_block",
 *   admin_label = @Translation("Moving Quote (Standalone)"),
 * )
 */
class MovingQuoteBlock extends BlockBase {
  public function build() {
    return [
      '#type' => 'markup',
      '#markup' => \\Drupal::formBuilder()->getForm('Drupal\driving_distance_calculator\Form\MovingQuoteForm'),
    ];
  }
}
