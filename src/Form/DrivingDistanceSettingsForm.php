<?php

namespace Drupal\driving_distance_calculator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form for Driving Distance Calculator.
 */
class DrivingDistanceSettingsForm extends ConfigFormBase {

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

    $form['google_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#default_value' => $config->get('google_api_key'),
      '#required' => TRUE,
    ];

    $form['base_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Base Price'),
      '#default_value' => $config->get('base_price') ?? 10,
      '#step' => 0.01,
      '#min' => 0,
    ];

    $form['pricing_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Pricing mode'),
      '#options' => [
        'flat' => $this->t('Flat rates'),
        'tiered' => $this->t('Tiered per-km'),
        'per_minute' => $this->t('Per-minute pricing'),
        'hybrid' => $this->t('Hybrid (km + minutes)'),
        'custom' => $this->t('Custom formula (future use)'),
      ],
      '#default_value' => $config->get('pricing_mode') ?? 'tiered',
    ];

    $form['per_minute_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate per minute'),
      '#default_value' => $config->get('per_minute_rate') ?? 0.5,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="pricing_mode"]' => ['value' => 'per_minute'],
        ],
      ],
    ];

    $form['hybrid_distance_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Hybrid distance rate (per km)'),
      '#default_value' => $config->get('hybrid_distance_rate') ?? 1.0,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="pricing_mode"]' => ['value' => 'hybrid'],
        ],
      ],
    ];

    $form['hybrid_time_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Hybrid time rate (per minute)'),
      '#default_value' => $config->get('hybrid_time_rate') ?? 0.2,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="pricing_mode"]' => ['value' => 'hybrid'],
        ],
      ],
    ];

    $form['fragile_multiplier'] = [
      '#type' => 'number',
      '#title' => $this->t('Fragile multiplier'),
      '#default_value' => $config->get('fragile_multiplier') ?? 1.2,
      '#step' => 0.1,
      '#min' => 1,
    ];

    $form['weight_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Per-kg weight rate'),
      '#default_value' => $config->get('weight_rate') ?? 0.5,
      '#step' => 0.01,
      '#min' => 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('driving_distance_calculator.settings')
      ->set('google_api_key', $form_state->getValue('google_api_key'))
      ->set('base_price', $form_state->getValue('base_price'))
      ->set('pricing_mode', $form_state->getValue('pricing_mode'))
      ->set('per_minute_rate', $form_state->getValue('per_minute_rate'))
      ->set('hybrid_distance_rate', $form_state->getValue('hybrid_distance_rate'))
      ->set('hybrid_time_rate', $form_state->getValue('hybrid_time_rate'))
      ->set('fragile_multiplier', $form_state->getValue('fragile_multiplier'))
      ->set('weight_rate', $form_state->getValue('weight_rate'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
