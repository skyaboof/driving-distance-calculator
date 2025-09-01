<?php

namespace Drupal\driving_distance_calculator\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\driving_distance_calculator\Service\PriceCalculatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Calculates and stores distance, cost, and advanced pricing inputs.
 *
 * @WebformHandler(
 * id = "driving_distance_calculator_handler",
 * label = @Translation("Store Distance & Cost (Unified)"),
 * category = @Translation("Calculation"),
 * description = @Translation("Performs server-side calculations and stores all pricing inputs."),
 * cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 * results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class StoreDistanceCostWebformHandler extends WebformHandlerBase {

  protected $isExecuting = FALSE;
  protected $httpClient;
  protected $priceCalculator;
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    $instance->priceCalculator = $container->get('driving_distance_calculator.price_calculator');
    $instance->configFactory = $container->get('config.factory');
    
    // THE FIX: Directly set the protected logger property instead of calling setLogger().
    $instance->logger = $container->get('logger.factory')->get('driving_distance_calculator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'origin_element' => 'origin_address',
      'destination_element' => 'destination_address',
      'distance_element' => 'calculated_distance',
      'cost_element' => 'calculated_cost',
      'weight_element' => 'shipment_weight',
      'fragile_element' => 'fragile_items',
      'priority_element' => 'priority_shipping',
      'delivery_time_element' => 'delivery_time',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = $this->buildElementOptions();
    $fields = [
      'origin_element' => $this->t('Origin element'),
      'destination_element' => $this->t('Destination element'),
      'distance_element' => $this->t('Distance element (to store result)'),
      'cost_element' => $this->t('Cost element (to store result)'),
      'weight_element' => $this->t('Weight (kg) element'),
      'fragile_element' => $this->t('Fragile element (checkbox/radios)'),
      'priority_element' => $this->t('Priority element (checkbox/radios)'),
      'delivery_time_element' => $this->t('Delivery time element (timestamp)'),
    ];

    foreach ($fields as $key => $label) {
      $form[$key] = [
        '#type' => 'select',
        '#title' => $label,
        '#options' => $options,
        '#default_value' => $this->configuration[$key] ?? '',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    foreach (array_keys($this->defaultConfiguration()) as $key) {
      if ($form_state->hasValue($key)) {
        $this->configuration[$key] = $form_state->getValue($key);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    if ($this->isExecuting) {
      return;
    }
    $this->isExecuting = TRUE;

    $flat_data = $this->flattenArray($webform_submission->getData());
    $config = $this->configFactory->get('driving_distance_calculator.settings');
    $api_key = $config->get('google_api_key');

    $origin = $flat_data[$this->configuration['origin_element']] ?? NULL;
    $destination = $flat_data[$this->configuration['destination_element']] ?? NULL;

    if (empty($api_key) || empty($origin) || empty($destination)) {
      $this->isExecuting = FALSE;
      return;
    }

    try {
      $url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
      $response = $this->httpClient->request('GET', $url, ['query' => ['origins' => $origin, 'destinations' => $destination, 'key' => $api_key, 'units' => 'metric']]);
      $api_data = json_decode($response->getBody(), TRUE);

      if (($api_data['rows'][0]['elements'][0]['status'] ?? 'ERROR') === 'OK') {
        $distance_km = ($api_data['rows'][0]['elements'][0]['distance']['value'] ?? 0) / 1000;
        $duration_minutes = ($api_data['rows'][0]['elements'][0]['duration']['value'] ?? 0) / 60;

        // Get all optional values using the flattened data.
        $weight_kg = (float) ($flat_data[$this->configuration['weight_element']] ?? 0);
        $fragile = !empty($flat_data[$this->configuration['fragile_element']]);
        $priority = !empty($flat_data[$this->configuration['priority_element']]);
        $delivery_timestamp = $flat_data[$this->configuration['delivery_time_element']] ?? NULL;

        $options = [
          'priority' => $priority,
          'requested_delivery_timestamp' => $delivery_timestamp,
        ];

        // Perform the full calculation.
        $price_result = $this->priceCalculator->calculate($distance_km, $duration_minutes, $weight_kg, $fragile, $options);

        if (isset($price_result['cost'])) {
          $webform_submission->setElementData($this->configuration['cost_element'], $price_result['cost']);
          $webform_submission->setElementData($this->configuration['distance_element'], round($distance_km, 2) . ' km');
          $webform_submission->save();
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->error('Error calling Google Distance Matrix API: @message', ['@message' => $e->getMessage()]);
    }

    $this->isExecuting = FALSE;
  }

  /**
   * Recursively flattens nested submission arrays for easy lookup.
   */
  protected function flattenArray(array $array, string $prefix = ''): array {
    $result = [];
    foreach ($array as $key => $value) {
      $new_key = $prefix ? $prefix . '[' . $key . ']' : $key;
      if (is_array($value)) {
        // Use array_merge to ensure deeper keys overwrite previous ones.
        $result = array_merge($result, $this->flattenArray($value, $new_key));
      }
      else {
        $result[$new_key] = $value;
        $result[$key] = $value; // Convenience key.
      }
    }
    return $result;
  }

  /**
   * Build a flat options list of all element keys in the webform.
   */
  protected function buildElementOptions(): array {
    $elements = $this->getWebform()->getElementsDecodedAndFlattened();
    $options = [];
    foreach ($elements as $key => $element) {
        $options[$key] = $element['#title'] ? "{$element['#title']} ({$key})" : $key;
    }
    return $options;
  }
}