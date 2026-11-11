<?php

/**
 * @file
 * Preprocess functions for paragraphs.
 */

declare(strict_types=1);

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityChangedInterface;
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

/**
 * Implements hook_preprocess_HOOK() for paragraph--policy-content.
 *
 * Gives each Heading 2 in the text an id and lists them as the "On this
 * page" contents. "Last updated" is the page's last change.
 */
function pimlico_preprocess_paragraph__policy_content(array &$variables): void {
  $paragraph = $variables['paragraph'];
  $item = $paragraph->get('field_text')->first();
  $html = $item ? (string) $item->value : '';
  $contents = [];

  if ($html !== '') {
    $dom = Html::load($html);
    $used = [];
    foreach ($dom->getElementsByTagName('h2') as $heading) {
      $title = trim($heading->textContent);
      if ($title === '') {
        continue;
      }
      $id = $heading->getAttribute('id') ?: Html::getClass($title);
      $base = $id;
      for ($i = 2; isset($used[$id]); $i++) {
        $id = $base . '-' . $i;
      }
      $used[$id] = TRUE;
      $heading->setAttribute('id', $id);
      $contents[] = ['id' => $id, 'title' => $title];
    }
    $html = Html::serialize($dom);
  }

  $parent = $paragraph->getParentEntity();
  $updated = $parent instanceof EntityChangedInterface
    ? \Drupal::service('date.formatter')->format($parent->getChangedTime(), 'custom', 'j F Y')
    : '';

  $variables['policy'] = [
    'updated' => $updated,
    'contents' => $contents,
    'body' => [
      '#type' => 'processed_text',
      '#text' => $html,
      '#format' => $item ? $item->format : 'basic_html',
    ],
  ];
  if ($parent) {
    CacheableMetadata::createFromRenderArray($variables)
      ->addCacheableDependency($parent)
      ->applyTo($variables);
  }
}
