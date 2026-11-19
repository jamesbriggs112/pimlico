<?php

/**
 * @file
 * Post-update functions for Pimlico Brands.
 */

declare(strict_types=1);

use Drupal\paragraphs\Entity\Paragraph;

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

/**
 * Brand pages are built from sections: About, Quote, Gallery, Specialisms.
 *
 * Moves each Brand's fixed About heading and text, quote, gallery and credit,
 * and specialisms into Introduction, Quote, Image gallery and Info rows
 * sections on field_sections, in the Figma order, with the eyebrows and
 * headings the old template printed. A section is added only where the old
 * page showed one; a gallery credit without gallery photos was never shown
 * and has nowhere to go.
 *
 * A post-update, not a deploy hook: `drush deploy` runs post-updates before
 * the configuration import that deletes the old fields, and deploy hooks
 * after it. So this also adds field_sections to Brand from the sync
 * directory when the import hasn't yet. Re-runnable: a Brand that already
 * has sections is skipped.
 */
function pimlico_brands_post_update_brand_sections(array &$sandbox): string {
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!isset($sandbox['ids'])) {
    $field_storage = \Drupal::entityTypeManager()->getStorage('field_config');
    if (!$field_storage->load('node.brand.field_sections')) {
      $record = \Drupal::service('config.storage.sync')->read('field.field.node.brand.field_sections');
      if (!$record) {
        throw new \RuntimeException('field.field.node.brand.field_sections is missing from the sync directory.');
      }
      // Keeps the exported UUID, so the configuration import leaves it be.
      $field_storage->createFromStorageRecord($record)->save();
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    }
    $old_fields = array_keys(array_intersect_key(
      \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'brand'),
      array_flip(['field_heading', 'body', 'field_quote', 'field_gallery', 'field_caption', 'field_specialisms']),
    ));
    if (count($old_fields) < 6) {
      $sandbox['#finished'] = 1;
      return 'Brand sections: the old Brand fields are gone, so there is nothing to move.';
    }
    $sandbox['ids'] = array_values($node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'brand')
      ->notExists('field_sections')
      ->sort('nid')
      ->execute());
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['done'] = [];
    $sandbox['drafts'] = [];
  }

  $sections = static function ($brand): array {
    $sections = [];
    $heading = (string) $brand->get('field_heading')->value;
    $body = $brand->get('body')->first();
    if ($heading !== '' || ($body && $body->value)) {
      $sections[] = Paragraph::create([
        'type' => 'introduction',
        'field_eyebrow' => 'About the house',
        // The old template fell back to the Brand's name; the field is
        // required on the section.
        'field_heading' => $heading !== '' ? $heading : $brand->label(),
        'field_text' => $body ? ['value' => $body->value, 'format' => $body->format] : NULL,
      ]);
    }
    if ($quote = $brand->get('field_quote')->entity) {
      $sections[] = $quote->createDuplicate();
    }
    if (!$brand->get('field_gallery')->isEmpty()) {
      $sections[] = Paragraph::create([
        'type' => 'image_gallery',
        'field_heading' => 'Inside the showroom',
        'field_caption' => $brand->get('field_caption')->value,
        'field_images' => $brand->get('field_gallery')->getValue(),
      ]);
    }
    $rows = array_map(
      static fn($row) => $row->createDuplicate(),
      $brand->get('field_specialisms')->referencedEntities(),
    );
    if ($rows) {
      $sections[] = Paragraph::create([
        'type' => 'info_rows',
        'field_eyebrow' => 'Specialisms',
        'field_heading' => 'What you’ll find',
        // The old template printed info rows tinted, as Figma does.
        'field_background' => 'linen',
        'field_items' => $rows,
      ]);
    }
    return $sections;
  };

  foreach ($node_storage->loadMultiple(array_splice($sandbox['ids'], 0, 10)) as $brand) {
    if ((int) $node_storage->getLatestRevisionId($brand->id()) !== (int) $brand->getRevisionId()) {
      $sandbox['drafts'][] = $brand->id();
    }
    $brand->set('field_sections', $sections($brand));
    $brand->setNewRevision(TRUE);
    $brand->setRevisionLogMessage('About, quote, gallery and specialisms moved into sections (pimlico_brands_post_update_brand_sections).');
    $brand->setSyncing(TRUE);
    $brand->save();
    $sandbox['done'][] = $brand->id();
  }

  $sandbox['#finished'] = $sandbox['ids'] ? count($sandbox['done']) / $sandbox['total'] : 1;
  if ($sandbox['ids']) {
    return '';
  }
  $message = sprintf('Brand sections: %d of %d Brands moved to sections.', count($sandbox['done']), $sandbox['total']);
  if ($sandbox['drafts']) {
    $message .= ' These Brands have an unpublished draft newer than the published page, which still has the old fields; re-enter its changes: node ' . implode(', ', $sandbox['drafts']) . '.';
  }
  return $message;
}
