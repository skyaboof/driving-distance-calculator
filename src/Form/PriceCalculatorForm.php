<?php

namespace Drupal\driving_distance_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\driving_distance_calculator\Service\PriceCalculatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

class PriceCalculatorForm extends FormBase {

  protected PriceCalculatorInterface $calculator;

  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->calculator = $container->get('driving_distance_calculator.price_calculator');
    return $instance;
  }

  public function getFormId() {
    return 'price_calculator_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $cfg = \Drupal::config('driving_distance_calculator.settings');

    $ajax = ['callback' => [$this, 'updatePrice'], 'event' => 'change', 'wrapper' => 'price-breakdown'];

    $form['base_price'] = [
      '#type' => 'number',
      '#title' => $this->t('Base Price'),
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.01,
      '#default_value' => (float) $cfg->get('base_price') ?: 5.0,
      '#ajax' => $ajax,
    ];

    $form['distance'] = [
      '#type' => 'number',
      '#title' => $this->t('Distance (km)'),
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.1,
      '#ajax' => $ajax,
      '#description' => $this->t('Enter distance directly here (the Webform handler will calculate from addresses server‑side).'),
    ];

    $form['minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Time (minutes)'),
      '#required' => TRUE,
      '#min' => 0,
      '#ajax' => $ajax,
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight (kg)'),
      '#required' => FALSE,
      '#min' => 0,
      '#step' => 0.1,
      '#ajax' => $ajax,
    ];

    $form['fragile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fragile'),
      '#ajax' => $ajax,
    ];

    $form['breakdown'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'price-breakdown'],
      'content' => [
        '#markup' => $this->t('Enter values to see the estimated price.'),
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
      '#ajax' => $ajax,
    ];

    return $form;
  }

  /**
   * Validate user inputs before AJAX update/submit.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $distance = $form_state->getValue('distance');
    $minutes = $form_state->getValue('minutes');
    $weight = $form_state->getValue('weight');

    $errors = [];

    if (!is_numeric($distance) || $distance < 0) {
      $form_state->setErrorByName('distance', $this->t('Distance must be a non-negative number.'));
    }
    if (!is_numeric($minutes) || $minutes < 0) {
      $form_state->setErrorByName('minutes', $this->t('Time (minutes) must be a non-negative number.'));
    }
    if ($weight !== '' && (!is_numeric($weight) || $weight < 0)) {
      $form_state->setErrorByName('weight', $this->t('Weight must be a non-negative number.'));
    }
  }

  public function updatePrice(array $form, FormStateInterface $form_state) {
    // If validation errors exist, show them in the AJAX response.
    if ($form_state->hasAnyErrors()) {
      $messages = ['#type' => 'status_messages'];
      $form['breakdown']['content'] = $messages;
      return $form['breakdown'];
    }

    $cfg = \Drupal::config('driving_distance_calculator.settings');

    $distance = (float) $form_state->getValue('distance');
    $minutes = (float) $form_state->getValue('minutes');
    $weight = $form_state->getValue('weight') !== '' ? (float) $form_state->getValue('weight') : 0.0;
    $fragile = (bool) $form_state->getValue('fragile');
    $base_price = (float) $form_state->getValue('base_price');

    $conditions = [
      'weight' => $weight,
      'fragile' => $fragile,
      'pricing_mode' => (string) $cfg->get('pricing_mode') ?: 'flat',
      'base_price' => $base_price,
      'rate_per_km' => (float) $cfg->get('rate_per_km') ?: 0.5,
      'rate_per_min' => (float) $cfg->get('rate_per_min') ?: 0.2,
      'rate_per_kg' => (float) $cfg->get('rate_per_kg') ?: 0.3,
      'fragile_surcharge' => (float) $cfg->get('fragile_surcharge') ?: 10.0,
      'tiers' => $cfg->get('tiers') ?: [],
    ];

    $result = $this->calculator->calculate($distance, $minutes, $conditions);

    if ($result['status'] === 'success') {
      $form['breakdown']['content'] = [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Base (@base)', ['@base' => $conditions['base_price']]),
          $this->t('Distance × rate'),
          $this->t('Time × rate'),
          $this->t('Weight × rate'),
          $this->t('Fragile surcharge: @surcharge', ['@surcharge' => $result['surcharge']]),
          $this->t('Total: @total', ['@total' => $result['total_price']]),
        ],
      ];
    }
    else {
      $escaped = array_map([Html::class, 'escape'], $result['errors']);
      $form['breakdown']['content'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['messages', 'messages--error']],
        'errors' => ['#theme' => 'item_list', '#items' => $escaped],
      ];
    }

    return $form['breakdown'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
