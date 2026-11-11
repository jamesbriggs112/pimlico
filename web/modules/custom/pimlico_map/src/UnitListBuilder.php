<?php

declare(strict_types=1);

namespace Drupal\pimlico_map;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\pimlico_map\Entity\UnitInterface;

/**
 * Lists Units at /admin/content/units, by name.
 */
final class UnitListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 100;

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds(): array {
    return $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('label')
      ->pager($this->limit)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'label' => $this->t('Name'),
      'area' => $this->t('Area'),
      'buildings' => $this->t('Map building'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof UnitInterface);
    $buildings = $entity->getMapBuildings();
    return [
      'label' => $entity->label(),
      'area' => $entity->get('area')->entity?->label() ?? '',
      'buildings' => $buildings ? implode(', ', $buildings) : $this->t('Not on the map'),
    ] + parent::buildRow($entity);
  }

}
