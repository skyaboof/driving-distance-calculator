<?php

namespace Drupal\driving_distance_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class MovingQuoteForm extends FormBase {

  public function getFormId() {
    return 'moving_quote_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Attach library & price endpoint settings
    $form['#attached']['library'][] = 'driving_distance_calculator/price_calc';
    $form['#attached']['drupalSettings']['drivingDistanceCalculator']['priceEndpoint'] = \\Drupal::url('driving_distance_calculator.price_calc_endpoint');

    $step = $form_state->get('step') ?: 1;
    $values = $form_state->getValues();

    // Page 1: Contact Information
    if ($step == 1) {
      $form['#title'] = $this->t('1. Your Contact Information');
      $form['contact_information'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Your Contact Information'),
      ];
      $form['contact_information']['full_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Full Name'),
        '#required' => TRUE,
        '#default_value' => $values['full_name'] ?? '',
        '#attributes' => ['data-price-calc' => '1'],
      ];
      $form['contact_information']['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email Address'),
        '#required' => TRUE,
        '#default_value' => $values['email'] ?? '',
      ];
      $form['contact_information']['phone_number'] = [
        '#type' => 'tel',
        '#title' => $this->t('Phone Number'),
        '#required' => TRUE,
        '#default_value' => $values['phone_number'] ?? '',
      ];
      $form['contact_information']['how_did_you_hear'] = [
        '#type' => 'select',
        '#title' => $this->t('How did you hear about us?'),
        '#options' => [
          '' => '- Select One -',
          'google' => 'Google Search',
          'social_media' => 'Social Media',
          'referral' => 'Referral',
          'yelp' => 'Yelp',
          'other' => 'Other',
        ],
        '#default_value' => $values['how_did_you_hear'] ?? '',
      ];

      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => [[get_class($this), 'stepNext']],
      ];
    }

    // Page 2: Service Selection
    if ($step == 2) {
      $form['#title'] = $this->t('2. Select Your Service');
      $form['service_type'] = [
        '#type' => 'select',
        '#title' => $this->t('What type of service do you need?'),
        '#options' => [
          '' => '- Select a Service -',
          'local_residential_move' => 'Local Residential Move',
          'local_commercial_move' => 'Local Commercial/Office Move',
          'long_distance_move' => 'Long-Distance Move',
          'specialty_item_move' => 'Specialty Item Move (Standalone)',
          'diy_truck_rental' => 'DIY Truck Rental',
          'on_demand_delivery' => 'On-Demand / Small Delivery',
        ],
        '#required' => TRUE,
        '#default_value' => $values['service_type'] ?? '',
        '#attributes' => ['data-price-calc' => '1'],
      ];

      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => [[get_class($this), 'stepBack']],
      ];
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => [[get_class($this), 'stepNext']],
      ];
    }

    // Page 3: Move Details (condensed but comprehensive)
    if ($step == 3) {
      $form['#title'] = $this->t('3. Move Details');
      $form['booking'] = [
        '#type' => 'date',
        '#title' => $this->t('Preferred Service Date'),
        '#default_value' => $values['booking'] ?? '',
      ];
      $form['date_flexibility'] = [
        '#type' => 'radios',
        '#title' => $this->t('How flexible are you with your preferred move date?'),
        '#options' => [
          'not_flexible' => 'Not flexible at all (date is fixed)',
          'plus_minus_few_days' => 'Flexible +/- a few days',
          'very_flexible' => 'Very flexible (within the month)',
        ],
        '#default_value' => $values['date_flexibility'] ?? 'not_flexible',
      ];
      $form['universal_item_file'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload a Photo or Video of item(s) or conditions (optional)'),
        '#upload_location' => 'public://moving_quote_uploads/',
        '#default_value' => $values['universal_item_file'] ?? NULL,
        '#upload_validators' => [
          'file_validate_extensions' => ['jpg png gif mp4 mov webm'],
          'file_validate_size' => [20971520],
        ],
      ];

      $form['address'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Address'),
      ];
      $form['address']['origin_address'] = [
        '#type' => 'textfield',
        '#title' => $this->t('From'),
        '#default_value' => $values['origin_address'] ?? '',
        '#attributes' => ['data-price-calc' => '1'],
      ];
      $form['address']['destination_address'] = [
        '#type' => 'textfield',
        '#title' => $this->t('To'),
        '#default_value' => $values['destination_address'] ?? '',
        '#attributes' => ['data-price-calc' => '1'],
      ];

      $form['local_residential_move_details'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Local Residential Move Details'),
      ];
      $form['local_residential_move_details']['move_size_residential'] = [
        '#type' => 'select',
        '#title' => $this->t('Move Size'),
        '#options' => [
          'studio' => 'Studio Apartment',
          '1_br' => '1 Bedroom Home/Apt',
          '2_br' => '2 Bedroom Home/Apt',
          '3_br' => '3 Bedroom Home/Apt',
          '4_br_plus' => '4+ Bedroom Home/Apt',
          'few_items' => 'Just a few items',
        ],
        '#default_value' => $values['move_size_residential'] ?? '',
        '#attributes' => ['data-price-calc' => '1'],
      ];
      $form['local_residential_move_details']['origin_access_conditions'] = [
        '#type' => 'select',
        '#title' => $this->t('Origin Access Conditions'),
        '#options' => [
          'ground_floor' => 'Ground floor / No stairs ($0)',
          'stairs' => 'Stairs (Walk-up) ($10–$25 per flight or flat $50–$100)',
          'passenger_elevator' => 'Passenger Elevator ($20–$50 flat fee)',
          'freight_elevator' => 'Freight Elevator ($10–$30 or no surcharge)',
        ],
        '#default_value' => $values['origin_access_conditions'] ?? '',
      ];
      $form['local_residential_move_details']['origin_stairs_flights'] = [
        '#type' => 'number',
        '#title' => $this->t('Number of flights of stairs'),
        '#min' => 1,
        '#default_value' => $values['origin_stairs_flights'] ?? '',
      ];
      $form['local_residential_move_details']['destination_access_conditions'] = [
        '#type' => 'select',
        '#title' => $this->t('Destination Access Conditions'),
        '#options' => [
          'ground_floor' => 'Ground floor / No stairs ($0)',
          'stairs' => 'Stairs (Walk-up) ($10–$25 per flight or flat $50–$100)',
          'passenger_elevator' => 'Passenger Elevator ($20–$50 flat fee)',
          'freight_elevator' => 'Freight Elevator ($10–$30 or no surcharge)',
        ],
        '#default_value' => $values['destination_access_conditions'] ?? '',
      ];
      $form['local_residential_move_details']['destination_stairs_flights'] = [
        '#type' => 'number',
        '#title' => $this->t('Number of flights of stairs'),
        '#min' => 1,
        '#default_value' => $values['destination_stairs_flights'] ?? '',
      ];
      $form['local_residential_move_details']['parking_situation'] = [
        '#type' => 'select',
        '#title' => $this->t('Parking Situation at Both Locations'),
        '#options' => [
          'close' => 'Truck can park close to the entrance ($0)',
          'long_carry' => 'Long carry required (> 50 feet) ($50–$100 flat fee)',
          'shuttle' => 'Shuttle service may be needed ($150–$350 flat fee)',
        ],
        '#default_value' => $values['parking_situation'] ?? '',
      ];

      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => [[get_class($this), 'stepBack']],
      ];
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => [[get_class($this), 'stepNext']],
      ];
    }

    // Page 4: Add-ons and Review
    if ($step == 4) {
      $form['#title'] = $this->t('4. Optional Add-ons & Final Details');

      $form['additional_services_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Optional Add-on Services'),
      ];
      $form['additional_services_fieldset']['packing_services'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add Professional Packing Services'),
        '#default_value' => !empty($values['packing_services']),
      ];
      $form['additional_services_fieldset']['cleaning_services'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add Move-In / Move-Out Cleaning'),
        '#default_value' => !empty($values['cleaning_services']),
      ];

      // Compute estimated cost using PricingCalculator service and show review.
      $calculator = \\Drupal::service('driving_distance_calculator.pricing_calculator');
      $result = $calculator->compute($values);

      $details_html = '';
      foreach ($result['details'] as $d) {
        $details_html .= '<p>' . $this->t($d) . '</p>';
      }
      $details_html .= '<p><strong>' . $this->t('Total Estimated Cost: $@total', ['@total' => number_format($result['total'], 2)]) . '</strong></p>';

      $form['estimated_review'] = [
        '#type' => 'markup',
        '#markup' => '<div class="estimated-total">' . $details_html . '</div>',
        '#allowed_tags' => ['div', 'p', 'strong'],
      ];

      // Hidden and read-only fields for storing computed values
      $form['final_calculated_quote'] = [
        '#type' => 'hidden',
        '#value' => $result['total'],
        '#attributes' => ['name' => 'final_calculated_quote'],
      ];
      $form['calculated_cost'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Final Cost'),
        '#readonly' => TRUE,
        '#default_value' => '$' . number_format($result['total'], 2),
      ];
      $form['calculated_distance'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Calculated distance (m)'),
        '#readonly' => TRUE,
        '#default_value' => $result['distance_m'],
      ];

      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => [[get_class($this), 'stepBack']],
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit Quote Request'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  public static function stepNext(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?: 1;
    $form_state->set('step', $step + 1);
    $form_state->setRebuild(TRUE);
  }

  public static function stepBack(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?: 1;
    $form_state->set('step', max(1, $step - 1));
    $form_state->setRebuild(TRUE);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if ($step == 2) {
      $service = $form_state->getValue('service_type');
      if (empty($service)) {
        $form_state->setErrorByName('service_type', $this->t('Please select a service type.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Handle file permanence
    if (!empty($values['universal_item_file'])) {
      $fids = $values['universal_item_file'];
      if (!empty($fids)) {
        foreach ($fids as $fid) {
          $file = File::load($fid);
          if ($file) {
            $file->setPermanent();
            $file->save();
            \\Drupal::service('file.usage')->add($file, 'driving_distance_calculator', 'moving_quote', 1);
          }
        }
      }
    }

    // Compute final pricing and store
    $calculator = \\Drupal::service('driving_distance_calculator.pricing_calculator');
    $result = $calculator->compute($values);

    // Basic storage in state (replace with a custom entity or database table for production)
    $timestamp = \\Drupal::time()->getRequestTime();
    $store = \\Drupal::state();
    $key = 'driving_distance_calculator.submission.' . $timestamp;
    $store->set($key, ['data' => $values, 'result' => $result]);

    \\Drupal::messenger()->addMessage($this->t('Your quote request has been submitted. Estimated total: $@total', ['@total' => number_format($result['total'], 2)]));
    $form_state->setRedirect('driving_distance_calculator.moving_quote');
  }
}
