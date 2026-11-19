<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\pimlico_brands\BrandGroups;

/**
 * Implements hook_preprocess_HOOK() for node--brand.
 *
 * Adds brand_category_label for the card eyebrow: the Figma group
 * (Interiors, Antiques & art), or a Food & Drink Brand's own category.
 */
function pimlico_preprocess_node__brand(array &$variables): void {
  $term = $variables['node']->get('field_brand_category')->entity;
  if (!$term) {
    return;
  }
  $groups = \Drupal::service(BrandGroups::class);
  $variables['brand_category_label'] = $groups->labelFor($term);
  CacheableMetadata::createFromRenderArray($variables)
    ->addCacheableDependency($term)
    ->addCacheableDependency($groups->topCategory($term))
    ->applyTo($variables);
}

/**
 * Implements hook_preprocess_HOOK() for node--podcast-episode--full.
 *
 * Adds audio_url for the player. The player needs the file's URL, not the
 * rendered field, so this adds the media access check and cache tags that
 * rendering the field would have.
 */
function pimlico_preprocess_node__podcast_episode__full(array &$variables): void {
  $media = $variables['node']->get('field_audio')->entity;
  if (!$media) {
    return;
  }
  $access = $media->access('view', NULL, TRUE);
  $cache = CacheableMetadata::createFromRenderArray($variables)
    ->addCacheableDependency($media)
    ->addCacheableDependency($access);
  $file = $media->get('field_media_audio_file')->entity;
  if ($access->isAllowed() && $file) {
    $cache->addCacheableDependency($file);
    $variables['audio_url'] = $file->createFileUrl();
  }
  $cache->applyTo($variables);
}
