<?php

/**
 * @file
 * Builds two example Stories from the Figma frames, for layout review.
 *
 * @code
 * vendor/bin/drush php:script scripts/dev/seed-example-stories.php
 * @endcode
 *
 * - Press article (Figma 160:4149, "T12 Press article - Publication"):
 *   Molly Alexander's shop, with the Michelin Guide as its Publication.
 *   /news/molly-alexander-putting-chic-antiques
 * - Article (Figma 25:3143, "T08 News article"): the Figma placeholder copy.
 *   /news/placeholder-story-title
 *
 * Idempotent: Stories are found by bundle and title, and their fields and
 * sections replaced; photos are found by Media name and reused. Both are
 * published. The photos and logo were exported from Figma and live in
 * scripts/dev/example-stories/.
 *
 * The copy is the Figma copy. Figma mixes the Michelin Guide (logo and
 * publication line) with Financial Times copy (intro, quote, credits); it is
 * kept as Figma has it for review.
 *
 * MICHELIN GUIDE LOGO: a placeholder for design review only. It is the
 * publication's trade mark; get the publication's permission (or use the
 * logo from its press kit under its terms) before this goes live, or remove
 * it. The source link is the Michelin Guide home page, not the article.
 */

declare(strict_types=1);

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;

$etm = \Drupal::entityTypeManager();
$nodes = $etm->getStorage('node');
$src = __DIR__ . '/example-stories';

// News Categories by UUID (Structure Sync keeps UUIDs across environments).
$the_street = '9d46879b-8298-4256-9e69-4da677c274f4';
$antiques = '686574e0-1a10-4e2a-9bb9-620a06a6a094';
$term = static fn(string $uuid) => current($etm->getStorage('taxonomy_term')->loadByProperties(['uuid' => $uuid])) ?: NULL;

/**
 * Creates (or finds) an Image Media item from a file in example-stories/.
 *
 * The focal point (percentages) keeps the subject in view when the photo is
 * cropped to a different shape, as Figma frames it.
 */
$image = static function (string $file, string $name, string $alt, ?array $focal = NULL) use ($etm, $src): MediaInterface {
  $found = $etm->getStorage('media')->loadByProperties(['bundle' => 'image', 'name' => $name]);
  $media = reset($found);
  if (!$media) {
    $dir = 'public://example-stories';
    \Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    $saved = \Drupal::service('file.repository')->writeData((string) file_get_contents("$src/$file"), "$dir/$file", FileExists::Replace);
    $media = Media::create([
      'bundle' => 'image',
      'name' => $name,
      'uid' => 1,
      'field_media_image' => ['target_id' => $saved->id()],
    ]);
  }
  $media->get('field_media_image')->alt = $alt;
  $media->setPublished();
  $media->save();
  if ($focal && \Drupal::moduleHandler()->moduleExists('focal_point')) {
    $item = $media->get('field_media_image')->first();
    $manager = \Drupal::service('focal_point.manager');
    $crop = $manager->getCropEntity($item->entity, 'focal_point');
    $manager->saveCropEntity($focal[0], $focal[1], (int) $item->width, (int) $item->height, $crop);
  }
  // Valet doesn't hand missing .jpg/.png derivatives to Drupal (they 404),
  // so make the "large" one the Image section uses now.
  $uri = $media->get('field_media_image')->entity->getFileUri();
  $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('large');
  if ($style && !file_exists($style->buildUri($uri))) {
    $style->createDerivative($uri, $style->buildUri($uri));
  }
  return $media;
};

$p = static fn(string $type, array $values) => Paragraph::create(['type' => $type] + $values);
$text = static fn(string $html) => ['value' => $html, 'format' => 'basic_html'];
$paras = static fn(array $lines) => implode('', array_map(static fn($line) => "<p>$line</p>", $lines));
$rich = static fn(string $heading, string $html) => $p('text_wysiwyg_', [
  'field_heading' => $heading,
  'field_text' => $text($html),
]);
$quote = static fn(string $words, string $name, string $role) => $p('quote', [
  'field_text' => $text("<p>“{$words}”</p>"),
  'field_name' => $name,
  'field_position_company' => $role,
]);

/**
 * Creates or updates a published Story with these values.
 */
$story = static function (string $bundle, string $title, string $alias, array $values) use ($nodes): NodeInterface {
  $node = current($nodes->loadByProperties(['type' => $bundle, 'title' => $title]))
    ?: Node::create(['type' => $bundle, 'title' => $title, 'uid' => 1]);
  foreach ($values as $field => $value) {
    $node->set($field, $value);
  }
  $node->set('path', ['alias' => $alias, 'pathauto' => FALSE]);
  $node->set('moderation_state', 'published');
  $node->setNewRevision(TRUE);
  $node->setRevisionLogMessage('Example Story from Figma (scripts/dev/seed-example-stories.php).');
  $node->save();
  echo $node->bundle(), ' ', $node->id(), ' ', $alias, "\n";
  return $node;
};

// Press article (T12, Publication variant).
$shop = $image(
  'molly-alexander-shop.jpg',
  'Example: Molly Alexander in her shop',
  'Molly Alexander in her Pimlico Road shop, beside a carved console table with candlesticks and an antler trophy on the wall',
  [42, 52],
);
// The Source band's own photo in Figma. The template shows the lead image
// there, so this is in the Media library for when it can be used.
$image(
  'molly-alexander-shopfront.jpg',
  'Example: Molly Alexander shopfront',
  'The black-painted Molly Alexander shopfront on the corner of Pimlico Road',
  [55, 45],
);
$logo = $image('michelin-guide-logo.png', 'Example: Michelin Guide logo (placeholder)', 'Michelin Guide');
$brands = [];
// Figma order. Jamb has no Brand page yet, so it is skipped until it does.
foreach (['Jamb', 'Sibyl Colefax & John Fowler', 'Rose Uniacke'] as $name) {
  if ($brand = current($nodes->loadByProperties(['type' => 'brand', 'title' => $name]))) {
    $brands[] = $brand;
  }
}
$story('press_article', 'Molly Alexander is putting the chic in antiques', '/news/molly-alexander-putting-chic-antiques', [
  'field_summary' => 'The soprano turned dealer is playing a new tune on Pimlico Road',
  'field_seo_description' => 'The FT visits Molly Alexander’s new corner shop on Pimlico Road, where the former classical soprano is setting out to make buying antiques feel relaxed, personal and anything but intimidating.',
  'field_publication' => 'Michelin Guide',
  'field_publication_logo' => $logo,
  'field_publication_date' => '2026-06-03',
  'field_author' => 'Aimee Farrell',
  'field_source_link' => ['uri' => 'https://guide.michelin.com/gb/en'],
  'field_image' => $shop,
  'field_news_category' => $term($antiques),
  'field_credits' => 'Words: Aimee Farrell, Financial Times. Photography: Edward Rollitt.',
  'field_related' => $brands,
  'field_sections' => [
    $rich('A shop, not a showroom', $paras([
      'Alexander’s 1,200 sq ft corner space sits among the decorators and designers who line the street, a few doors from Lord Snowdon’s former studio. She’s clear it’s a shop: somewhere to take your time, look closely and relax.',
      'Inside, a marble-topped George II console, a painted Dutch leather screen and a Spanish four-poster set the tone, with glassware, ceramics and curiosities arranged so that each piece talks to the one beside it.',
      'The building is an early example of British social housing, kept deliberately plain with white walls, bamboo blinds and stripped floors, so the objects do the talking.',
    ])),
    $rich('From the concert hall to Pimlico Road', $paras([
      'Before antiques, Alexander sang professionally as a classical soprano, performing at venues including the Sydney Opera House and Carnegie Hall.',
      'Her eye was shaped by her great aunt Min Hogg, founding editor of The World of Interiors, who took her round country houses and museums and pushed her to keep stretching her taste.',
      'She moved into antiques in 2016, working with Will Fisher at Jamb, just along Pimlico Road, and went on to run its antiques department before opening her own shop.',
    ])),
    $quote('I want to take the stuffiness out of buying antiques.', 'Molly Alexander', 'Financial Times · June 2026'),
    $rich('An eye for the unexpected', $paras([
      'She specialises in 18th-century English and Irish pieces, but her stock runs from ancient finds to 1960s and 70s furniture, chosen simply because it catches her eye.',
      'Peers and clients rate her highly. Designer Jasper Conran calls her one of the people whose taste he trusts most.',
      'Alongside the shop, she advises private clients on building collections, decorating with objects that carry strong provenance and history.',
    ])),
  ],
]);

// News article (T08). Figma's copy is placeholder throughout.
$lead = $image(
  'ebury-street-corner.jpg',
  'Example: Ebury Street corner',
  'Georgian brick terrace on the corner of Ebury Street, with the Pinch and Timothy Langston shopfronts and clipped box in pots',
  [50, 55],
);
$supporting = $image(
  'pimlico-road-terrace.jpg',
  'Example: Pimlico Road terrace',
  'Red-brick terrace on Pimlico Road with the Fermoie and Newson’s Yard shopfronts, as a cyclist passes',
  [50, 60],
);
$lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.';
$sed = 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit.';
$excepteur = 'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore.';
$story('article', 'Placeholder story title', '/news/placeholder-story-title', [
  'field_summary' => 'Placeholder short introduction · people, craft and place.',
  'field_seo_description' => 'Placeholder short introduction · people, craft and place.',
  'field_author' => 'Placeholder author',
  'field_reading_time' => 6,
  'field_image' => $lead,
  'field_caption' => 'Placeholder lead image caption and photographer credit.',
  'field_news_category' => $term($the_street),
  'field_credits' => 'Words: placeholder author · Photography: placeholder contributor',
  // Figma shows a Brand, an event and a Story; Related stories takes Stories
  // only, so three existing ones fill the row.
  'field_related' => array_values(array_filter(array_map(
    static fn(string $title) => current($nodes->loadByProperties(['type' => 'article', 'title' => $title])) ?: NULL,
    [
      'A morning on the street with the antique dealers',
      'The makers of Newson’s Yard',
      'The pub at the end of the road',
    ],
  ))),
  'field_sections' => [
    // The standfirst under the lead image. There is no standfirst field, so
    // it is a Text section with no heading.
    $rich('', $paras(['Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea.'])),
    $rich('Placeholder opening section', $paras([$lorem, $sed, $excepteur])),
    $rich('Placeholder story subheading', $paras([$sed, $excepteur, $lorem])),
    $quote('Placeholder quotation that draws out the voice of the interviewee.', 'Placeholder name', 'role'),
    $p('image', [
      'field_layout' => 'default',
      'field_image_with_caption' => $p('image_with_caption', [
        'field_image' => $supporting,
        'field_caption' => 'Placeholder image caption and photographer credit.',
      ]),
    ]),
    $rich('Placeholder context & detail', $paras([$excepteur, $lorem, $sed])
      . '<ul><li>Placeholder first supporting point</li><li>Placeholder second supporting point</li><li>Placeholder third supporting point</li></ul>'),
    $rich('Placeholder closing section', $paras([$lorem, $excepteur, $sed])),
  ],
]);
