<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\pimlico_brands\BrandGroups;

/**
 * Implements hook_preprocess_HOOK() for node--brand.
 *
 * Adds brand_category: { label, url, section, section_url } for the card
 * eyebrow and the Brand page breadcrumb. Interiors and Antiques & art Brands
 * show their Figma group and link to it on Brands; Food & Drink Brands show
 * their own category on Dine.
 */
function pimlico_preprocess_node__brand(array &$variables): void {
  $node = $variables['node'];
  $term = $node->get('field_brand_category')->entity;
  $groups = \Drupal::service(BrandGroups::class);
  $group = $groups->groupFor($term);

  if ($group) {
    $variables['brand_category'] = [
      'label' => $groups->labels()[$group],
      'url' => Url::fromRoute('view.brands.page_brands', [], ['query' => ['category' => $group]])->toString(),
      'section' => (string) t('Brands'),
      'section_url' => Url::fromRoute('view.brands.page_brands')->toString(),
    ];
  }
  elseif ($term) {
    $variables['brand_category'] = [
      'label' => $term->label(),
      'url' => Url::fromRoute('view.brands.page_dine', [], ['query' => ['category' => $term->id()]])->toString(),
      'section' => (string) t('Dine'),
      'section_url' => Url::fromRoute('view.brands.page_dine')->toString(),
    ];
  }
  if ($term) {
    CacheableMetadata::createFromRenderArray($variables)
      ->addCacheableDependency($term)
      ->applyTo($variables);
  }
}
