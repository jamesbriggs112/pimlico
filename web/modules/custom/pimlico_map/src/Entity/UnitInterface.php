<?php

declare(strict_types=1);

namespace Drupal\pimlico_map\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * A shopfront on the street map that Brands trade from.
 */
interface UnitInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Returns the Map Building IDs the Unit covers, in order.
   *
   * @return string[]
   *   Building or shop-unit IDs from the base map, e.g. "w535769837".
   */
  public function getMapBuildings(): array;

  /**
   * Returns the address on one line, or an empty string.
   */
  public function getAddressLine(): string;

}
