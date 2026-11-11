<?php

declare(strict_types=1);

namespace Drupal\pimlico_map;

use Drupal\node\NodeInterface;

/**
 * Builds render arrays for the <pimlico-map> element.
 *
 * The element fetches the base map and the Tenancy Feed itself, so these
 * render arrays don't vary with Brand content and carry no Brand cache tags.
 */
final class MapBuilder {

  public function __construct(
    private readonly MapAssets $assets,
    private readonly BrandFeed $feed,
  ) {}

  /**
   * Returns the overview map (Visit page, "The street").
   *
   * @param string $id
   *   The element ID, which the category filter bar points at.
   */
  public function overview(string $id = 'street-map'): array {
    return $this->element([
      '#map_id' => $id,
      '#locate' => TRUE,
    ]);
  }

  /**
   * Returns the map zoomed to a Brand, or an empty array when it's not on it.
   */
  public function brand(NodeInterface $brand): array {
    $build = ['#cache' => ['tags' => $brand->getCacheTags()]];
    foreach ($brand->get('field_units')->referencedEntities() as $unit) {
      $build['#cache']['tags'] = array_merge($build['#cache']['tags'], $unit->getCacheTags());
    }
    if (!$this->feed->isOnMap($brand)) {
      return $build;
    }
    return $this->element([
      '#map_id' => 'brand-map',
      '#mode' => 'brand',
      '#brand' => $this->feed->mapId($brand),
    ]) + $build;
  }

  /**
   * Returns the map in pick mode, for the Unit form.
   *
   * @param string $target
   *   The ID of the widget wrapper whose inputs the picked building fills.
   */
  public function picker(string $target): array {
    $build = $this->element([
      '#map_id' => $target . '-map',
      '#pick' => TRUE,
      '#attributes' => ['data-picker-for' => $target],
    ]);
    $build['#attached']['library'][] = 'pimlico_map/picker';
    return $build;
  }

  /**
   * Returns the base element with the asset URLs and library.
   */
  private function element(array $settings): array {
    return $settings + [
      '#theme' => 'pimlico_map',
      '#src' => $this->assets->baseMapUrl(),
      '#feed' => $this->assets->feedUrl(),
      '#attached' => ['library' => ['pimlico_map/pimlico-map']],
    ];
  }

}
