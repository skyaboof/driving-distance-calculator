<?php

namespace Drupal\driving_distance_calculator\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the CalculationLog entity.
 *
 * @ContentEntityType(
 *   id = "calculation_log",
 *   label = @Translation("Calculation Log"),
 *   base_table = "calculation_log",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class CalculationLog extends ContentEntityBase implements ContentEntityInterface {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['distance'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Distance'))
      ->setDescription(t('Calculated distance.'));

    $fields['minutes'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Minutes'))
      ->setDescription(t('Time in minutes.'));

    $fields['weight'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Weight'))
      ->setDescription(t('Weight in kg.'));

    $fields['fragile'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Fragile'))
      ->setDescription(t('Fragile flag.'));

    $fields['total_price'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Total Price'))
      ->setDescription(t('Calculated total price.'));

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('Calculation timestamp.'));

    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP Address'))
      ->setDescription(t('Client IP address.'));

    return $fields;
  }

}