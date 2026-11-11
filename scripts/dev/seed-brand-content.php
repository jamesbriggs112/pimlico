<?php

/**
 * @file
 * Fills out Brand pages with sample content for layout review and QA.
 *
 *   drush php:script scripts/dev/seed-brand-content.php
 *
 * Follows the Figma Brand page (03 Brand with Map): an "About the house"
 * heading and two paragraphs, a pull quote, a gallery with a photography
 * credit, three "What you'll find" rows and a trade note.
 *
 * Only empty fields are filled, so editors' content is never overwritten.
 * The about text is built from each Brand's own summary and address; the
 * heading, specialisms and trade note are category-level sample copy, and
 * the photos are the theme's sample images. No contact details are made up.
 * Replace all of it with the client's copy and photography before launch.
 */

declare(strict_types=1);

use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;

$etm = \Drupal::entityTypeManager();
$photos = array_values(array_filter(
  $etm->getStorage('media')->loadByProperties(['bundle' => 'image']),
  static fn($m) => str_starts_with($m->label(), 'Sample: '),
));
if (!$photos) {
  echo "No sample photos: run scripts/dev/seed-pages.php first.\n";
  return;
}

// Category sample copy, keyed by top-level Brand Category.
$copy = [
  'Home & Interiors' => [
    'heading' => 'Rooms that look as though they have always been there, made with fabrics, colour and furniture chosen over years',
    'more' => 'The showroom is set out as a sequence of rooms rather than a shop floor, so pieces can be seen together and at their true scale. Visit to see the current collection and talk through a project.',
    'specialisms' => [
      ['Furniture', 'Pieces for every room, old and new'],
      ['Fabrics & wallpapers', 'Collections to see and handle in person'],
      ['Interior projects', 'Whole houses and single rooms'],
    ],
  ],
  'Antiques and art' => [
    'heading' => 'Pieces with a past, chosen with a dealer’s eye and shown as they would live in a house',
    'more' => 'Stock changes often, and much of it never reaches a catalogue, so the showroom is the place to see what is new. The team can advise on provenance, care and placing a piece.',
    'specialisms' => [
      ['Antique furniture', 'A changing selection'],
      ['Works of art', 'Paintings, sculpture and objects'],
      ['Sourcing', 'Help finding a particular piece'],
    ],
  ],
  'Soft furnishings and fabrics' => [
    'heading' => 'Cloth, colour and pattern, to see and handle before it becomes curtains, cushions and upholstery',
    'more' => 'Fabrics are hung and folded so you can see how they drape and how colours sit together. Bring swatches, paint colours or a plan of the room.',
    'specialisms' => [
      ['Fabrics', 'Printed, woven and plain'],
      ['Linens & bedding', 'For every room in the house'],
      ['Made to measure', 'Curtains, blinds and cushions'],
    ],
  ],
  'Lighting' => [
    'heading' => 'Light made to be lived with, from table lamps to pieces that hold a whole room together',
    'more' => 'Seeing a light switched on in a room tells you more than any photograph, so the showroom is lit as a house would be. The team can help with scale, finish and placing.',
    'specialisms' => [
      ['Pendants & chandeliers', 'For halls, stairs and dining rooms'],
      ['Wall & table lights', 'Finishes to see in person'],
      ['Bespoke', 'Sizes and finishes made to order'],
    ],
  ],
  'Food & Drink' => [
    'heading' => 'Somewhere to stop on Pimlico Road, between showrooms or at the end of the day',
    'more' => 'Check opening times before you visit, and book ahead for larger tables.',
    'specialisms' => [
      ['Eat', 'See the menu on their website'],
      ['Drink', 'From morning coffee to the evening'],
      ['Book', 'Reservations through their website'],
    ],
  ],
];

$terms = $etm->getStorage('taxonomy_term');
$updated = 0;
$brands = $etm->getStorage('node')->loadByProperties(['type' => 'brand']);
ksort($brands);
$i = 0;
foreach ($brands as $brand) {
  assert($brand instanceof NodeInterface);
  $i++;
  $category = $brand->get('field_brand_category')->entity;
  if ($category) {
    $parents = $terms->loadAllParents($category->id());
    $category = end($parents) ?: $category;
  }
  $c = $copy[$category ? $category->label() : ''] ?? $copy['Home & Interiors'];
  $summary = trim((string) $brand->get('field_summary')->value);
  $address = $brand->get('field_address')->first();
  $street = $address ? trim($address->getAddressLine1()) : '';
  $changed = FALSE;

  $fill = static function (string $field, $value) use ($brand, &$changed): void {
    if ($brand->hasField($field) && $brand->get($field)->isEmpty() && $value) {
      $brand->set($field, $value);
      $changed = TRUE;
    }
  };

  $fill('field_heading', $c['heading']);
  $body = '<p>' . htmlspecialchars($summary ?: $brand->label() . ' trades on Pimlico Road.') . '</p>';
  $body .= '<p>' . htmlspecialchars(($street ? 'Find ' . $brand->label() . ' at ' . $street . '. ' : '') . $c['more']) . '</p>';
  $fill('body', ['value' => $body, 'format' => 'basic_html']);

  // The pull quote is the first sentence of the Brand's own summary.
  if ($summary && $brand->get('field_quote')->isEmpty()) {
    $first = preg_split('/(?<=[.!?])\s+/', $summary)[0];
    $brand->set('field_quote', Paragraph::create([
      'type' => 'quote',
      'field_text' => ['value' => '<p>“' . htmlspecialchars(rtrim($first, '.')) . '.”</p>', 'format' => 'basic_html'],
      'field_name' => $brand->label(),
    ]));
    $changed = TRUE;
  }

  // Seven photos, a different run for each Brand, as the Figma gallery.
  $gallery = [];
  for ($n = 0; $n < 7; $n++) {
    $gallery[] = $photos[($i + $n) % count($photos)];
  }
  $fill('field_gallery', $gallery);
  $fill('field_caption', 'Photography: sample images');

  if ($brand->get('field_specialisms')->isEmpty()) {
    $brand->set('field_specialisms', array_map(static fn($row) => Paragraph::create([
      'type' => 'info_row',
      'field_heading' => $row[0],
      'field_text' => ['value' => '<p>' . htmlspecialchars($row[1]) . '</p>', 'format' => 'basic_html'],
    ]), $c['specialisms']));
    $changed = TRUE;
  }
  if (($category ? $category->label() : '') !== 'Food & Drink') {
    $fill('field_trade_info', 'Trade accounts and sourcing on request');
  }

  if ($changed) {
    $brand->setNewRevision(TRUE);
    $brand->setRevisionLogMessage('Sample content for layout review (scripts/dev/seed-brand-content.php).');
    $brand->setSyncing(TRUE);
    $brand->save();
    $updated++;
  }
}
echo "$updated of " . count($brands) . " Brands filled out.\n";
