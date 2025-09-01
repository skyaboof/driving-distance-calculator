<?php

namespace Drupal\driving_distance_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Simple admin controller to clear distance cache.
 */
class CacheController extends ControllerBase {

  public function clearAll() {
    $tag = 'driving_distance_calculator:distances';
    \Drupal::service('cache_tags.invalidator')->invalidateTags([$tag]);
    $this->messenger()->addStatus($this->t('Driving distance lookup cache cleared.'));
    $url = Url::fromRoute('driving_distance_calculator.settings_form');
    return new RedirectResponse($url->toString());
  }

}
