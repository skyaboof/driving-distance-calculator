<?php

declare(strict_types=1);

namespace Drupal\driving_distance_calculator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for the Driving Distance Calculator module.
 */
class SettingsForm extends ConfigFormBase {

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

    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider'),
      '#description' => $this->t('Select the routing/distance provider you will use.'),
      '#options' => [
        'openrouteservice' => $this->t('OpenRouteService'),
        'google' => $this->t('Google Distance Matrix'),
        'mapbox' => $this->t('Mapbox'),
      ],
      '#default_value' => $config->get('provider') ?? 'openrouteservice',
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('API key for the selected provider.'),
      '#default_value' => $config->get('api_key') ?? '',
      '#maxlength' => 255,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array & $form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array & $form, FormStateInterface $form_state) {
    $this->config('driving_distance_calculator.settings')
      ->set('provider', $form_state->getValue('provider'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
