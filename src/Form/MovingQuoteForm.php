<?php

namespace Drupal\driving_distance_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Moving Quote standalone form.
 */
class MovingQuoteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'driving_distance_calculator_moving_quote_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Example fields — adapt names/types to match your original webform.
    $form['service_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Service type'),
      '#options' => [
        'residential' => $this->t('Residential'),
        'commercial' => $this->t('Commercial'),
      ],
    ];

    $form['move_size_residential'] = [
      '#type' => 'select',
      '#title' => $this->t('Move size'),
      '#options' => [
        'studio' => $this->t('Studio'),
        '1_bed' => $this->t('1 bedroom'),
        '2_bed' => $this->t('2 bedroom'),
        '3_bed' => $this->t('3 bedroom'),
      ],
    ];

    $form['origin_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Origin address'),
    ];
    $form['destination_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination address'),
    ];

    // Add hidden fields used by the JS to write results (optional).
    $form['calculated_cost'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Calculated cost'),
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['calculated_distance'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Calculated distance (m)'),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    // Attach the JS library and send the endpoint via drupalSettings.
    $form['#attached']['library'][] = 'driving_distance_calculator/price_calc';

    // Explicitly mark this form so JS can find it reliably (data attribute).
    $form['#attributes']['data-driving-distance-form'] = '1';

    // Provide the endpoint url to JS
    $form['#attached']['drupalSettings']['drivingDistanceCalculator'] = [
      'priceEndpoint' => \Drupal::url('driving_distance_calculator.price_calc_endpoint', [], ['absolute' => TRUE]),
    ];

    // Submit button (the JS makes AJAX requests automatically; you can still submit if desired).
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Request Quote'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // For now just rebuild (or leave as no-op; the JS handles live calculations).
    $this->messenger()->addStatus($this->t('Request received (UI uses AJAX for live pricing).'));
  }

}