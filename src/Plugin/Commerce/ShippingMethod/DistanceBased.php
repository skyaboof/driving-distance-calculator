<?php

namespace Drupal\driving_distance_calculator\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\state_machine\WorkflowManagerInterface;

/**
 * Provides the DistanceBased shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "distance_based",
 *   label = @Translation("Distance Based"),
 * )
 */
class DistanceBased extends ShippingMethodBase {

  /**
   * Constructs a new DistanceBased object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   The workflow manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, WorkflowManagerInterface $workflow_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager, $workflow_manager);
    $this->services['default'] = new ShippingService('default', $this->t('Distance based'));
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    $calculator = \Drupal::service('driving_distance_calculator.price_calculator');
    $config = \Drupal::config('driving_distance_calculator.settings');
    $origin = $config->get('origin_address');
    $destination = $shipment->getShippingProfile()->get('address')->postal_code; // Simplify, use full address in practice.

    // Simulate distance, in real, use lookup.
    $distance = 10; // Placeholder, implement lookup.
    $minutes = 20;
    $weight = $shipment->getWeight()->getNumber();
    $fragile = false; // Add logic if needed.

    $result = $calculator->calculate($distance, $minutes, ['weight' => $weight, 'fragile' => $fragile]);

    if ($result['status'] !== 'success') {
      return [];
    }

    $amount = new Price((string) $result['total_price'], $result['currency']);

    $rates = [];
    $rates[] = new ShippingRate([
      'shipping_method_id' => $this->parentEntity->id(),
      'service' => $this->services['default'],
      'amount' => $amount,
    ]);

    return $rates;
  }

}