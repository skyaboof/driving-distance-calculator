<?php

namespace Drupal\driving_distance_calculator\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Very simple First-Fit Decreasing (FFD) by volume and weight limit.
 * This is a heuristic; replace with a more advanced bin packer as needed.
 */
class Packer {

  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  /**
   * @param array $items Each: ['length'=>..,'width'=>..,'height'=>..,'weight'=>..]
   * @return array Boxes with packed items and aggregates.
   */
  public function pack(array $items): array {
    $cfg = $this->configFactory->get('driving_distance_calculator.settings');
    $boxes = $cfg->get('boxes') ?: [];
    if (empty($boxes)) return [];

    // Sort items by volume descending.
    usort($items, function ($a, $b) {
      $va = ($a['length']*$a['width']*$a['height']);
      $vb = ($b['length']*$b['width']*$b['height']);
      return $vb <=> $va;
    });

    $result = [];
    foreach ($items as $item) {
      $placed = false;
      foreach ($result as &$box) {
        if ($this->fits($box, $item)) {
          $box['items'][] = $item;
          $box['total_weight'] += $item['weight'];
          $placed = true;
          break;
        }
      }
      unset($box);

      if (!$placed) {
        // Pick the smallest box that can hold this item by volume & weight.
        $selected = NULL;
        foreach ($boxes as $candidate) {
          if ($this->itemFitsEmptyBox($candidate, $item)) {
            if (!$selected || $this->boxVolume($candidate) < $this->boxVolume($selected)) {
              $selected = $candidate;
            }
          }
        }
        if (!$selected) {
          // Fallback to largest box; in real life, throw exception or split item.
          $selected = end($boxes) ?: reset($boxes);
        }
        $result[] = [
          'box' => $selected,
          'items' => [$item],
          'total_weight' => $item['weight'],
        ];
      }
    }

    return $result;
  }

  protected function fits(array $boxEntry, array $item): bool {
    // Very naive: check weight limit only. Real fit would check orientation/space partition.
    $limit = (float) ($boxEntry['box']['weight_limit'] ?? 999999);
    return ($boxEntry['total_weight'] + $item['weight']) <= $limit;
  }

  protected function itemFitsEmptyBox(array $box, array $item): bool {
    $fitsDims = ($item['length'] <= $box['length'] && $item['width'] <= $box['width'] && $item['height'] <= $box['height'])
      || ($item['length'] <= $box['width'] && $item['width'] <= $box['length'] && $item['height'] <= $box['height']);
    $fitsWeight = $item['weight'] <= (float) ($box['weight_limit'] ?? 999999);
    return $fitsDims && $fitsWeight;
  }

  protected function boxVolume(array $box): float {
    return (float) $box['length']*$box['width']*$box['height'];
  }

}
