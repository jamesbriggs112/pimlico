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

/**
 * Listing pages that render their own title band and filters.
 *
 * @return array
 *   Settings keyed by "view_id:display_id".
 */
function _pimlico_listings(): array {
  return [
    'brands:page_brands' => [
      'vocabulary' => 'brand_category',
      'parent' => 0,
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

  if (isset($config['types'])) {
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
    $variables['promo'] = $view->display_handler->renderArea('footer');
    $variables['promo_after'] = 6;
  }
}
