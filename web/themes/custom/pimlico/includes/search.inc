<?php

/**
 * @file
 * Preprocess for the search page (Figma T17/T18).
 *
 * The page shows result counts per group in its filter bar and, with no
 * group chosen, three top hits per group. Views can't count other groups'
 * results, so the counts come from Search API queries here.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;

/**
 * Result groups in filter order, keyed by their search_group value.
 *
 * @return array
 *   Each group's label and "see all" link text.
 */
function _pimlico_search_groups(): array {
  return [
    'brands' => ['label' => t('Brands'), 'see_all' => t('See all brands')],
    'dine' => ['label' => t('Dine'), 'see_all' => t('See all places to eat')],
    'news' => ['label' => t('News'), 'see_all' => t('See all stories')],
    'press' => ['label' => t('Press'), 'see_all' => t('See all press')],
  ];
}

/**
 * Suggested searches shown before a search is made.
 *
 * @todo Make editable (design query in docs/build-plan.md, phase 4).
 */
function _pimlico_popular_searches(): array {
  return ['Antiques', 'Interiors', 'Lighting', 'Lunch', 'Newson’s Yard', 'Art'];
}

/**
 * Builds the search page variables for views-view--search--page-search.
 */
function _pimlico_preprocess_search_page(array &$variables): void {
  $cache = CacheableMetadata::createFromRenderArray($variables);
  $cache->addCacheContexts(['url.query_args:keys', 'url.query_args:type']);
  $cache->addCacheTags(['search_api_list:content']);

  $groups = _pimlico_search_groups();
  $request = \Drupal::request();
  $keys = trim((string) $request->query->get('keys', ''));
  $type = (string) $request->query->get('type', '');
  if (!isset($groups[$type])) {
    $type = '';
  }
  $link = fn(array $query) => Url::fromRoute('view.search.page_search', [], ['query' => $query])->toString();

  $search = [
    'keys' => $keys,
    'type' => $type,
    'action' => $link([]),
    'filters' => [],
    'groups' => [],
    'popular' => [],
    'total' => 0,
  ];

  if ($keys === '') {
    foreach (_pimlico_popular_searches() as $term) {
      $search['popular'][] = ['text' => $term, 'url' => $link(['keys' => $term]), 'active' => FALSE];
    }
    $variables['search'] = $search;
    $cache->applyTo($variables);
    return;
  }

  $counts = [];
  $index = Index::load('content');
  foreach (array_keys($groups) as $group) {
    $query = $index->query();
    $query->keys($keys);
    $query->addCondition('search_group', $group);
    $query->range(0, 0);
    $counts[$group] = $query->execute()->getResultCount();
  }
  $search['total'] = array_sum($counts);

  $search['filters'][] = [
    'text' => (string) t('All (@count)', ['@count' => $search['total']]),
    'url' => $link(['keys' => $keys]),
    'active' => $type === '',
  ];
  foreach ($groups as $group => $info) {
    $search['filters'][] = [
      'text' => (string) t('@label (@count)', ['@label' => $info['label'], '@count' => $counts[$group]]),
      'url' => $link(['keys' => $keys, 'type' => $group]),
      'active' => $type === $group,
    ];
    if ($type === '' && $counts[$group] > 0) {
      $search['groups'][] = [
        'id' => $group,
        'heading' => (string) \Drupal::translation()->formatPlural(
          $counts[$group],
          '@label · 1 result',
          '@label · @count results',
          ['@label' => $info['label']],
        ),
        'link_text' => (string) $info['see_all'],
        'link_url' => $link(['keys' => $keys, 'type' => $group]),
      ];
    }
  }

  $variables['search'] = $search;
  $cache->applyTo($variables);
}
