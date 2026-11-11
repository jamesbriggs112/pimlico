<?php

/**
 * @file
 * Preprocess functions for paragraphs.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\pimlico_map\MapBuilder;

/**
 * Implements hook_preprocess_HOOK() for paragraph--street-map.
 *
 * Adds the overview map (from pimlico_map) and the top-level Brand
 * Categories in weight order for the filter bar. The feed rolls
 * sub-categories up to these, so the names match the map's categories.
 */
function pimlico_preprocess_paragraph__street_map(array &$variables): void {
  $id = 'street-map-' . $variables['paragraph']->id();
  $categories = [];
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('brand_category', 0, 1);
  foreach ($terms as $term) {
    $categories[] = $term->name;
  }
  $variables['street_map'] = [
    'id' => $id,
    'categories' => $categories,
    'map' => \Drupal::service(MapBuilder::class)->overview($id),
  ];
  $cache = CacheableMetadata::createFromRenderArray($variables);
  $cache->addCacheTags(['taxonomy_term_list:brand_category']);
  $cache->applyTo($variables);
}
