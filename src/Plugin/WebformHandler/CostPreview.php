<?php

namespace Drupal\driving_distance_calculator\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformMarkup;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'cost_preview' element.
 *
 * @WebformElement(
 *   id = "cost_preview",
 *   label = @Translation("Cost Preview"),
 *   description = @Translation("Displays a live cost preview based on form values."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class CostPreview extends WebformMarkup {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#markup'] = $this->getCostPreview($webform_submission);
    parent::prepare($element, $webform_submission);
  }

  protected function getCostPreview(WebformSubmissionInterface $webform_submission) {
    $data = $webform_submission->getData();

    $distance = isset($data['calculated_distance']) ? (float) $data['calculated_distance'] : 0;
    $minutes = isset($data['minutes']) ? (float) $data['minutes'] : 0;
    $weight = isset($data['weight']) ? (float) $data['weight'] : 0;
    $fragile = !empty($data['fragile']);

    $calculator = \Drupal::service('driving_distance_calculator.price_calculator');
    $result = $calculator->calculate($distance, $minutes, ['weight' => $weight, 'fragile' => $fragile]);

    if ($result['status'] === 'success') {
      return $this->t('Estimated Cost: @total @currency', ['@total' => $result['total_price'], '@currency' => $result['currency']]);
    }
    return $this->t('Unable to calculate cost.');
  }
}

/**
 * Form element for CostPreview.
 *
 * @FormElement("cost_preview")
 */
class CostPreviewFormElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => FALSE,
    ];
  }
}