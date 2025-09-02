<?php

namespace Drupal\driving_distance_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Moving Quote block.
 *
 * @Block(
 *   id = "driving_distance_calculator_moving_quote_block",
 *   admin_label = @Translation("Moving Quote (Driving Distance Calculator)")
 * )
 */
class MovingQuoteBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Render the form in the block.
    return \Drupal::formBuilder()->getForm('Drupal\\driving_distance_calculator\\Form\\MovingQuoteForm');
  }

}