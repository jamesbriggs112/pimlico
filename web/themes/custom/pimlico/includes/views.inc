<?php

/**
 * @file
 * Theme and preprocess functions for views.
 *
 * Views can't build a filter bar of links from a taxonomy, or place an area
 * between rows of results, so the listing pages get those here.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\pimlico_brands\BrandGroups;

/**
 * Listing pages that render their own title band and filters.
 *
 * @return array
 *   Settings keyed by "view_id:display_id".
 */
function _pimlico_listings(): array {
  return [
    // Figma's category groups (pimlico_brands); Food & Drink is on Dine.
    'brands:page_brands' => [
      'groups' => TRUE,
      'param' => 'category',
      'align' => 'center',
      'label' => t('Filter brands by category'),
    ],
    'brands:page_dine' => [
      'vocabulary' => 'brand_category',
      'parent' => 'Food & Drink',
      'param' => 'category',
      'align' => 'center',
      'label' => t('Filter places to eat and drink'),
    ],
    'news:page_news' => [
      'types' => ['article' => t('News'), 'press_article' => t('Press')],
      'param' => 'type',
      'align' => 'center',
      'label' => t('Filter stories by type'),
    ],
  ];
}

/**
 * Implements hook_preprocess_HOOK() for views_view.
 */
function pimlico_preprocess_views_view(array &$variables): void {
  $view = $variables['view'];
  $key = $view->id() . ':' . $view->current_display;
  if ($key === 'search:page_search') {
    _pimlico_preprocess_search_page($variables);
    return;
  }
  $listings = _pimlico_listings();
  if (!isset($listings[$key])) {
    return;
  }
  $config = $listings[$key];
  $param = $config['param'];
  $cache = CacheableMetadata::createFromRenderArray($variables);
  $cache->addCacheContexts(['url.query_args:' . $param]);

  $current = (string) \Drupal::request()->query->get($param, '');
  $link = fn(array $query = []) => Url::fromRoute('<current>', [], ['query' => $query])->toString();
  $items = [
    [
      'text' => (string) t('All'),
      'url' => $link(),
      'active' => $current === '' || $current === 'All',
    ],
  ];

  if (!empty($config['groups'])) {
    foreach (\Drupal::service(BrandGroups::class)->labels() as $key => $label) {
      $items[] = [
        'text' => $label,
        'url' => $link([$param => $key]),
        'active' => $current === $key,
      ];
    }
  }
  elseif (isset($config['types'])) {
    foreach ($config['types'] as $type => $label) {
      $items[] = [
        'text' => (string) $label,
        'url' => $link([$param => $type]),
        'active' => $current === $type,
      ];
    }
  }
  else {
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $parent = $config['parent'];
    if (is_string($parent)) {
      $match = $storage->loadByProperties(['vid' => $config['vocabulary'], 'name' => $parent]);
      $parent = $match ? (int) reset($match)->id() : 0;
    }
    foreach ($storage->loadTree($config['vocabulary'], $parent, 1, TRUE) as $term) {
      $items[] = [
        'text' => $term->label(),
        'url' => $link([$param => $term->id()]),
        'active' => $current === (string) $term->id(),
      ];
    }
    $cache->addCacheTags(['taxonomy_term_list:' . $config['vocabulary']]);
  }

  $variables['listing'] = [
    'filters' => $items,
    'align' => $config['align'],
    'label' => (string) $config['label'],
  ];
  $cache->applyTo($variables);
}

/**
 * Implements hook_preprocess_HOOK() for views_view_unformatted.
 *
 * The Brands page shows its footer area (the promo block) after the sixth
 * card, as a full-width band, instead of after the results.
 */
function pimlico_preprocess_views_view_unformatted(array &$variables): void {
  $view = $variables['view'];
  if ($view->id() === 'brands' && $view->current_display === 'page_brands') {
    $variables['promo'] = _pimlico_promo_band($view->display_handler->getHandlers('footer')['entity_block_content'] ?? NULL);
    $variables['promo_after'] = 6;
  }
}

/**
 * Builds the promo band from the block content in a view's footer.
 *
 * Block content rendered as an entity has no template of its own, so the
 * block-content--promo template (used when it's placed as a block) never
 * applies here; this maps the block's fields onto pimlico:promo_band.
 *
 * @param object|null $area
 *   The view's "Rendered entity" area handler, if there is one.
 */
function _pimlico_promo_band(?object $area): array {
  $target = $area->options['target'] ?? '';
  if (!$target) {
    return [];
  }
  $storage = \Drupal::entityTypeManager()->getStorage('block_content');
  $block = current($storage->loadByProperties(['uuid' => $target])) ?: $storage->load($target);
  if (!$block || !$block->access('view')) {
    return [];
  }
  $link = $block->get('field_link')->first();
  $build = [
    '#type' => 'component',
    '#component' => 'pimlico:promo_band',
    '#props' => [
      'eyebrow' => (string) $block->get('field_eyebrow')->value,
      'heading' => (string) $block->get('field_heading')->value,
      'link_text' => $link ? (string) $link->title : '',
      'link_url' => $link ? $link->getUrl()->toString() : '',
    ],
    '#slots' => [
      'text' => $block->get('field_text')->view(['label' => 'hidden']),
      'image' => $block->get('field_image')->view([
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'settings' => ['view_mode' => 'full'],
      ]),
    ],
  ];
  CacheableMetadata::createFromObject($block)->applyTo($build);
  return $build;
}
