<?php

namespace Drupal\driving_distance_calculator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LogController extends ControllerBase {

  protected $entityTypeManager;

  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  public function listLogs() {
    $storage = $this->entityTypeManager->getStorage('calculation_log');
    $query = $storage->getQuery()
      ->sort('timestamp', 'DESC')
      ->pager(50);
    $ids = $query->execute();

    $logs = $storage->loadMultiple($ids);

    $rows = [];
    foreach ($logs as $log) {
      $rows[] = [
        date('Y-m-d H:i:s', $log->get('timestamp')->value),
        $log->get('distance')->value,
        $log->get('total_price')->value,
        $log->get('ip')->value,
      ];
    }

    $build = [
      '#type' => 'table',
      '#header' => [$this->t('Timestamp'), $this->t('Distance'), $this->t('Total Price'), $this->t('IP')],
      '#rows' => $rows,
      '#empty' => $this->t('No logs found.'),
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

}