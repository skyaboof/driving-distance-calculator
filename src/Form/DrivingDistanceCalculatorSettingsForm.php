<?php

namespace Drupal\driving_distance_calculator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configuration form for Driving Distance Calculator.
 *
 * NOTE: This extends pricing mode options without removing any prior behavior.
 */
class DrivingDistanceCalculatorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['driving_distance_calculator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'driving_distance_calculator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('driving_distance_calculator.settings');

    $form['#tree'] = TRUE;

    // Vertical tabs container.
    $form['general'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Driving Distance Calculator settings'),
    ];

    // General tab.
    $form['general_info'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#group' => 'general',
    ];
    $form['general_info']['google_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#default_value' => $config->get('google_api_key'),
      '#description' => $this->t('Server key with Distance Matrix API enabled.'),
    ];
    $form['general_info']['allow_client_fallback'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow client fallback'),
      '#default_value' => (bool) $config->get('allow_client_fallback'),
      '#description' => $this->t('If server lookup fails, allow using client-supplied distance (less secure).'),
    ];
    $form['general_info']['distance_lookup_retries'] = [
      '#type' => 'number',
      '#title' => $this->t('Distance Matrix retries'),
      '#default_value' => (int) $config->get('distance_lookup_retries'),
      '#min' => 0,
    ];
    $form['general_info']['cache_ttl_minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache TTL (minutes)'),
      '#default_value' => (int) $config->get('cache_ttl_minutes'),
      '#min' => 0,
    ];
    $form['general_info']['clear_cache'] = [
      '#markup' => $this->t(
        '<p><a href=":url" class="button button--secondary">Clear distance cache</a></p>',
        [':url' => Url::fromRoute('driving_distance_calculator.clear_cache')->toString()]
      ),
    ];

    // Pricing tab.
    $form['pricing'] = [
      '#type' => 'details',
      '#title' => $this->t('Pricing'),
      '#group' => 'general',
    ];
    $form['pricing']['pricing_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Pricing mode'),
      '#default_value' => $config->get('pricing_mode') ?: 'flat',
      '#options' => [
        'flat' => $this->t('Flat rates (base + km + min + weight + surcharge)'),
        'tiers' => $this->t('Tiered per-km (uses tiers table)'),
        'per_minute' => $this->t('Per-minute pricing (base + minutes)'),
        'hybrid' => $this->t('Hybrid (km + minutes)'),
      ],
      '#description' => $this->t('Choose how price components are applied. Existing modes keep their current behavior.'),
    ];
    $form['pricing']['base_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Base price'),
      '#default_value' => (float) $config->get('base_price') ?: 5.0,
      '#step' => 0.01,
      '#min' => 0,
    ];
    $form['pricing']['rate_per_km'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate per km'),
      '#default_value' => (float) $config->get('rate_per_km') ?: 0.5,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => [
        'visible' => [
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'flat']],
          'or',
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'hybrid']],
        ],
      ],
    ];
    $form['pricing']['rate_per_min'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate per minute'),
      '#default_value' => (float) $config->get('rate_per_min') ?: 0.2,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => [
        'visible' => [
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'flat']],
          'or',
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'per_minute']],
          'or',
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'hybrid']],
        ],
      ],
    ];
    $form['pricing']['rate_per_kg'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate per kg'),
      '#default_value' => (float) $config->get('rate_per_kg') ?: 0.3,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => [
        'visible' => [
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'flat']],
          'or',
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'tiers']],
          'or',
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'per_minute']],
          'or',
          [':input[name="pricing[pricing_mode]"]' => ['value' => 'hybrid']],
        ],
      ],
    ];
    $form['pricing']['fragile_surcharge'] = [
      '#type' => 'number',
      '#title' => $this->t('Fragile surcharge'),
      '#default_value' => (float) $config->get('fragile_surcharge') ?: 10.0,
      '#step' => 0.01,
      '#min' => 0,
    ];

    // Tiered pricing table (unchanged behavior).
    $form['pricing']['tiers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tiered pricing (if using "Tiered per-km")'),
      '#states' => [
        'visible' => [
          ':input[name="pricing[pricing_mode]"]' => ['value' => 'tiers'],
        ],
      ],
    ];
    $tiers = $config->get('tiers') ?: [
      ['max' => 10, 'price' => 2.5],
      ['max' => 50, 'price' => 2.0],
    ];
    $form['pricing']['tiers']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Max distance (km)'), $this->t('Price per km')],
    ];
    foreach ($tiers as $i => $tier) {
      $form['pricing']['tiers']['table'][$i]['max'] = [
        '#type' => 'number',
        '#step' => 0.1,
        '#default_value' => $tier['max'],
        '#min' => 0,
      ];
      $form['pricing']['tiers']['table'][$i]['price'] = [
        '#type' => 'number',
        '#step' => 0.01,
        '#default_value' => $tier['price'],
        '#min' => 0,
      ];
    }

    // --- New Advanced Pricing Logic ---
    $form['pricing']['advanced_pricing'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Pricing Logic'),
      '#open' => FALSE,
    ];
    $form['pricing']['advanced_pricing']['distance_tiers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Distance tiers'),
      '#description' => $this->t('JSON array of tiers: [{"max_km":50,"per_km_rate":1.0},...]'),
      '#default_value' => $config->get('distance_tiers') ?: '',
    ];
    $form['pricing']['advanced_pricing']['weight_tiers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Weight tiers'),
      '#description' => $this->t('JSON array of tiers: [{"max_kg":10,"extra_per_km":0},...]'),
      '#default_value' => $config->get('weight_tiers') ?: '',
    ];
    $form['pricing']['advanced_pricing']['fragile_surcharge_flat'] = [
      '#type' => 'number',
      '#title' => $this->t('Fragile surcharge (flat)'),
      '#step' => 0.01,
      '#default_value' => $config->get('fragile_surcharge_flat') ?? 0,
    ];
    $form['pricing']['advanced_pricing']['fragile_surcharge_pct'] = [
      '#type' => 'number',
      '#title' => $this->t('Fragile surcharge (%)'),
      '#step' => 0.1,
      '#default_value' => $config->get('fragile_surcharge_pct') ?? 0,
    ];
    $form['pricing']['advanced_pricing']['priority_multiplier'] = [
      '#type' => 'number',
      '#title' => $this->t('Priority multiplier'),
      '#step' => 0.01,
      '#default_value' => $config->get('priority_multiplier') ?? 1.25,
      '#description' => $this->t('Multiply total cost when priority shipping is requested (e.g. 1.5 = 50% more).'),
    ];
    $form['pricing']['advanced_pricing']['after_hours_surcharge_pct'] = [
      '#type' => 'number',
      '#title' => $this->t('After-hours surcharge (%)'),
      '#step' => 0.1,
      '#default_value' => $config->get('after_hours_surcharge_pct') ?? 0,
    ];
    $form['pricing']['advanced_pricing']['business_start_hour'] = [
      '#type' => 'number',
      '#title' => $this->t('Business hours start (0–23)'),
      '#min' => 0,
      '#max' => 23,
      '#default_value' => $config->get('business_start_hour') ?? 8,
    ];
    $form['pricing']['advanced_pricing']['business_end_hour'] = [
      '#type' => 'number',
      '#title' => $this->t('Business hours end (0–23)'),
      '#min' => 0,
      '#max' => 23,
      '#default_value' => $config->get('business_end_hour') ?? 18,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $tiers = $values['pricing']['tiers']['table'] ?? [];
    $normalized_tiers = [];
    foreach ($tiers as $row) {
      $normalized_tiers[] = [
        'max' => isset($row['max']) ? (float) $row['max'] : 0,
        'price' => isset($row['price']) ? (float) $row['price'] : 0,
      ];
    }

    $advanced = $values['pricing']['advanced_pricing'] ?? [];

    $this->config('driving_distance_calculator.settings')
      // General settings
      ->set('google_api_key', $values['general_info']['google_api_key'] ?? '')
      ->set('allow_client_fallback', (bool) ($values['general_info']['allow_client_fallback'] ?? FALSE))
      ->set('distance_lookup_retries', (int) ($values['general_info']['distance_lookup_retries'] ?? 0))
      ->set('cache_ttl_minutes', (int) ($values['general_info']['cache_ttl_minutes'] ?? 0))
      // Pricing settings
      ->set('pricing_mode', $values['pricing']['pricing_mode'] ?? 'flat')
      ->set('base_price', (float) ($values['pricing']['base_price'] ?? 0))
      ->set('rate_per_km', (float) ($values['pricing']['rate_per_km'] ?? 0))
      ->set('rate_per_min', (float) ($values['pricing']['rate_per_min'] ?? 0))
      ->set('rate_per_kg', (float) ($values['pricing']['rate_per_kg'] ?? 0))
      ->set('fragile_surcharge', (float) ($values['pricing']['fragile_surcharge'] ?? 0))
      ->set('tiers', $normalized_tiers)
      // Advanced pricing settings
      ->set('distance_tiers', $advanced['distance_tiers'] ?? '')
      ->set('weight_tiers', $advanced['weight_tiers'] ?? '')
      ->set('fragile_surcharge_flat', $advanced['fragile_surcharge_flat'] ?? 0)
      ->set('fragile_surcharge_pct', $advanced['fragile_surcharge_pct'] ?? 0)
      ->set('priority_multiplier', $advanced['priority_multiplier'] ?? 1)
      ->set('after_hours_surcharge_pct', $advanced['after_hours_surcharge_pct'] ?? 0)
      ->set('business_start_hour', $advanced['business_start_hour'] ?? 0)
      ->set('business_end_hour', $advanced['business_end_hour'] ?? 0)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
