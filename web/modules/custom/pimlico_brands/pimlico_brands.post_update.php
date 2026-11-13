<?php

/**
 * @file
 * Post-update functions for Pimlico Brands.
 */

declare(strict_types=1);

/**
 * Brand categories match the site: Interiors, Antiques & art, Food & Drink.
 *
 * Home & Interiors becomes Interiors and takes in Soft furnishings and
 * fabrics and Lighting; Antiques and art becomes Antiques & art. Food &
 * Drink Brands move to Pubs and restaurants or Cafés, which the Dine filter
 * uses. Brands keep their pages; only their category changes.
 */
function pimlico_brands_post_update_front_end_categories(): string {
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $nodes = \Drupal::entityTypeManager()->getStorage('node');
  $term = static function (string $name) use ($terms) {
    $found = $terms->loadByProperties(['vid' => 'brand_category', 'name' => $name]);
    return reset($found) ?: NULL;
  };
  $refile = static function (array $brand_ids, int $tid) use ($nodes): int {
    $count = 0;
    foreach ($nodes->loadMultiple($brand_ids) as $brand) {
      $brand->set('field_brand_category', $tid);
      $brand->setNewRevision(TRUE);
      $brand->setRevisionLogMessage('Brand categories simplified to match the site (Interiors, Antiques & art, Pubs and restaurants, Cafés).');
      $brand->setSyncing(TRUE);
      $brand->save();
      $count++;
    }
    return $count;
  };
  $brands_in = static fn($t) => $t ? $nodes->getQuery()->accessCheck(FALSE)->condition('type', 'brand')->condition('field_brand_category', $t->id())->execute() : [];
  $done = [];

  // Interiors.
  $interiors = $term('Home & Interiors') ?? $term('Interiors');
  if ($interiors) {
    $interiors->setName('Interiors')->set('weight', 0)->save();
    foreach (['Soft furnishings and fabrics', 'Lighting'] as $name) {
      if ($old = $term($name)) {
        $done[] = $refile($brands_in($old), (int) $interiors->id()) . " Brands from $name to Interiors";
        $old->delete();
      }
    }
  }

  // Antiques & art.
  if ($antiques = $term('Antiques and art')) {
    $antiques->setName('Antiques & art')->set('weight', 1)->save();
  }
  if ($food = $term('Food & Drink')) {
    $food->set('weight', 2)->save();
  }

  // Food & Drink Brands go to a Dine sub-category.
  $dine = [
    'Pubs and restaurants' => ['Hunan', 'The Orange Public House & Hotel', 'La Poule au Pot', 'Wildflowers Restaurant'],
    'Cafés' => ['Daylesford Organic', 'Hagen'],
  ];
  foreach ($dine as $name => $titles) {
    $sub = $term($name);
    if (!$sub) {
      continue;
    }
    $ids = $nodes->getQuery()->accessCheck(FALSE)->condition('type', 'brand')->condition('title', $titles, 'IN')->execute();
    $done[] = $refile($ids, (int) $sub->id()) . " Brands to $name";
  }

  return 'Brand categories simplified: ' . implode('; ', $done) . '.';
}
