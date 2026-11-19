<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\pimlico_brands\BrandGroups;

/**
 * The story types shown in News, keyed by bundle, with their public label.
 *
 * One list for the News type filter and the "News · 4 Nov" card meta, so a
 * card's label always matches its filter. Keyed by machine name, since an
 * editor can rename a content type's label in the UI.
 *
 * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
 *   Labels keyed by node bundle.
 */
function _pimlico_story_types(): array {
  return [
    'article' => t('News'),
    'press_article' => t('Press'),
  ];
}

/**
 * Implements hook_preprocess_HOOK() for node.
 *
 * Adds story_type, the public label of a story's type ("News", "Press"),
 * for the story card meta.
 */
function pimlico_preprocess_node(array &$variables): void {
  $variables['story_type'] = _pimlico_story_types()[$variables['node']->bundle()] ?? NULL;
}

/**
 * Implements hook_preprocess_HOOK() for node--brand.
 *
 * Adds brand_category_label for the card eyebrow: the Brand's own category
 * tag, such as "Cafés" or "Interiors" (BrandGroups::labelFor()).
 */
function pimlico_preprocess_node__brand(array &$variables): void {
  $term = $variables['node']->get('field_brand_category')->entity;
  if (!$term) {
    return;
  }
  $variables['brand_category_label'] = \Drupal::service(BrandGroups::class)->labelFor($term);
  CacheableMetadata::createFromRenderArray($variables)
    ->addCacheableDependency($term)
    ->applyTo($variables);
}
