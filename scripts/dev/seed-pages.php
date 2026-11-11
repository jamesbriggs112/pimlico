<?php

/**
 * @file
 * Builds the site's pages with the Figma copy, for local and staging set-up.
 *
 *   drush php:script scripts/dev/seed-pages.php
 *
 * Idempotent: pages are found by title and their sections replaced. Sample
 * photos come from the theme's Storybook images. Copy marked "Placeholder"
 * or lorem ipsum in Figma is placeholder here too; editors replace it.
 */

declare(strict_types=1);

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;

$etm = \Drupal::entityTypeManager();
$nodes = $etm->getStorage('node');

// Sample photos (shared with the Component test pages).
$media = [];
$src = DRUPAL_ROOT . '/themes/custom/pimlico/src/stories/images';
foreach (glob("$src/*.jpg") as $path) {
  $name = basename($path, '.jpg');
  $found = $etm->getStorage('media')->loadByProperties(['bundle' => 'image', 'name' => "Sample: $name"]);
  if (!$found) {
    $dir = 'public://samples';
    \Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    $file = \Drupal::service('file.repository')->writeData((string) file_get_contents($path), "$dir/$name.jpg", FileExists::Replace);
    $found = [Media::create(['bundle' => 'image', 'name' => "Sample: $name", 'field_media_image' => ['target_id' => $file->id(), 'alt' => ucfirst(str_replace('-', ' ', $name))]])];
    reset($found)->save();
  }
  $media[$name] = reset($found);
}
$film = current($etm->getStorage('media')->loadByProperties(['bundle' => 'video'])) ?: NULL;

$p = static fn(string $type, array $values) => Paragraph::create(['type' => $type] + $values);
$text = static fn(string $html) => ['value' => $html, 'format' => 'basic_html'];
$link = static fn(string $path, string $title) => ['uri' => str_starts_with($path, 'http') ? $path : 'internal:' . $path, 'title' => $title];
$brand = static fn(string $title) => current($nodes->loadByProperties(['type' => 'brand', 'title' => $title])) ?: NULL;
$lorem = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>';

/**
 * Creates or updates a published page with these sections.
 */
$page = static function (string $title, string $alias, string $summary, array $sections) use ($nodes): NodeInterface {
  $node = current($nodes->loadByProperties(['type' => 'page', 'title' => $title]))
    ?: Node::create(['type' => 'page', 'title' => $title, 'uid' => 1]);
  $node->set('field_summary', $summary);
  $node->set('field_sections', $sections);
  $node->set('path', ['alias' => $alias, 'pathauto' => FALSE]);
  $node->set('moderation_state', 'published');
  $node->save();
  echo $node->id(), ' ', $alias, "\n";
  return $node;
};

$getting_here = $p('info_rows', [
  'field_eyebrow' => 'Travel',
  'field_heading' => 'Getting here',
  'field_text' => $text('<p>Travel options for everyone visiting Pimlico Road.</p>'),
  'field_anchor' => 'getting-here',
  'field_items' => [
    $p('info_row', ['field_heading' => 'Public transport', 'field_text' => $text('<p>Sloane Square (District and Circle lines), about 6 minutes on foot. Placeholder bus routes.</p>')]),
    $p('info_row', ['field_heading' => 'Walking & cycling', 'field_text' => $text('<p>Placeholder: cycle stands on Bourne Street and Holbein Place.</p>')]),
    $p('info_row', ['field_heading' => 'Taxi & drop-off', 'field_text' => $text('<p>Placeholder: drop-off on Pimlico Road outside Newson’s Yard.</p>')]),
  ],
]);
$visit_banner = static fn(string $heading, string $body, string $button, string $path, string $eyebrow = 'Visit') => $p('visit_banner', [
  'field_eyebrow' => $eyebrow,
  'field_heading' => $heading,
  'field_text' => $text("<p>$body</p>"),
  'field_link' => $link($path, $button),
  'field_image' => $media['visit-banner-street-spire'],
]);

// Home (T01).
$home = $page('Home', '/home', 'London’s street of makers, dealers and interior houses.', [
  $p('hero_film', [
    'field_heading' => 'London’s street of makers, dealers and interior houses',
    'field_image' => $media['hero-street'],
    'field_video' => $film,
  ]),
  $p('introduction', [
    'field_eyebrow' => 'Brands',
    'field_heading' => 'One short street in Belgravia, home to antique dealers, furniture makers, fabric houses and a few good places for lunch',
    'field_text' => $text('<p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do. Eiusmod tempor incididunt.</p>'),
    'field_link' => $link('/about', 'About Pimlico Road'),
    'field_images' => [$media['intro-daylesford-terrace'], $media['intro-ebury-street-corner']],
  ]),
  $p('content_grid', [
    'field_eyebrow' => 'News',
    'field_heading' => 'This week on Pimlico Road',
    'field_link' => $link('/news', 'All news'),
  ]),
  $p('feature_banner', [
    'field_eyebrow' => 'Newson’s Yard',
    'field_caption' => 'Est. 1840',
    'field_heading' => 'A timber yard, reopened to makers',
    'field_text' => $text('<p>Elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut. Enim ad minim veniam quis nostrud.</p>'),
    'field_link' => $link('/newsons-yard', 'Step into Newson’s Yard'),
    'field_image' => $media['newsons-yard-band'],
  ]),
  $visit_banner('Plan your visit', 'Laboris nisi aliquip ex ea commodo consequat duis aute irure in reprehenderit. Voluptate velit.', 'Learn more', '/visit'),
]);
\Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node/' . $home->id())->save();

// Newson's Yard (T06).
$page('Newson’s Yard', '/newsons-yard', 'A timber yard, reopened to makers.', [
  $p('hero_film', [
    'field_heading' => 'Newson’s Yard, a timber yard reopened to makers',
    'field_image' => $media['newsons-yard-band'],
    'field_video' => $film,
  ]),
  $p('content_grid', [
    'field_heading' => 'Brands at the Yard',
    'field_text' => $text('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.</p>'),
    'field_link' => $link('/brands', 'All brands'),
    'field_anchor' => 'resident-brands',
    'field_background' => 'linen',
    'field_content' => array_filter([$brand('Modernity'), $brand('And Objects'), $brand('The Lacquer Company')]),
  ]),
  $p('introduction', [
    'field_eyebrow' => 'Introduction',
    'field_heading' => 'About Newson’s Yard',
    'field_text' => $text('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p><p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute.</p>'),
    // No images: the centred layout (Figma 23:763), with the film below.
    'field_video' => $film,
  ]),
  $visit_banner('Visit Newson’s Yard', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.', 'Plan your visit', '/visit'),
]);

// Visit (T13, with "The street" from the 03 Brand with Map page).
$page('Visit', '/visit', 'Plan a visit to Pimlico Road: the street, its showrooms and places to eat.', [
  $p('street_map', []),
  $p('info_band', [
    'field_eyebrow' => 'Location & directions',
    'field_heading' => 'Find Pimlico Road',
    'field_text' => $text('<p>Pimlico Road, London SW1W · Placeholder location details and postcode.</p>'),
    'field_links' => [$link('https://www.google.com/maps/search/?api=1&query=Pimlico+Road+London+SW1W', 'Get directions in Google Maps')],
    'field_image' => $media['visit-banner-street-spire'],
  ]),
  $getting_here,
]);

// About (T14).
$page('About Pimlico Road', '/about', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.', [
  $p('image', [
    'field_layout' => 'full_width',
    'field_image_with_caption' => $p('image_with_caption', ['field_image' => $media['visit-banner-street-spire']]),
  ]),
  $p('text_wysiwyg_', [
    'field_heading' => 'A place for people who care about making',
    'field_text' => $text($lorem . $lorem . $lorem),
  ]),
  $p('feature_banner', [
    'field_eyebrow' => 'Newson’s Yard',
    'field_caption' => 'Est. 1840',
    'field_heading' => 'From timber yard to design courtyard',
    'field_text' => $text('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod tempor incididunt.</p>'),
    'field_link' => $link('/visit', 'Plan your visit'),
    'field_image' => $media['newsons-yard-band'],
  ]),
  $p('split_text', [
    'field_eyebrow' => 'Heritage',
    'field_heading' => 'A street with a story',
    'field_intro' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.',
    'field_text' => $text('<h2>Craft, heritage and the street today</h2>' . $lorem . $lorem . $lorem),
    'field_background' => 'linen',
  ]),
  $p('content_grid', [
    'field_heading' => 'Meet the people behind the street',
    'field_text' => $text('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.</p>'),
    'field_content' => array_filter([
      current($nodes->loadByProperties(['type' => 'article', 'title' => 'The makers of Newson’s Yard'])) ?: NULL,
      $brand('Sibyl Colefax & John Fowler'),
    ]),
  ]),
  $p('cta', [
    'field_eyebrow' => 'Next step',
    'field_heading' => 'Come and explore',
    'field_text' => $text('<p>Follow the street from showrooms to the courtyard and places to pause.</p>'),
    'field_links' => [$link('/visit', 'Plan your visit'), $link('/brands', 'Discover the brands'), $link('/news', 'Hear a conversation')],
  ]),
]);

// Contact (T15).
$page('Contact', '/contact', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.', [
  $p('info_rows', [
    'field_eyebrow' => 'Enquiry routes',
    'field_heading' => 'How can we help?',
    'field_background' => 'white',
    'field_text' => $text('<p>Choose the right route for your enquiry.</p>'),
    'field_items' => [
      $p('info_row', ['field_heading' => 'General enquiries', 'field_text' => $text('<p>Placeholder team / email / expected response time.</p>')]),
      $p('info_row', ['field_heading' => 'Press enquiries', 'field_text' => $text('<p>Placeholder press contact and media request information.</p>')]),
      $p('info_row', ['field_heading' => 'Brand enquiries', 'field_text' => $text('<p>Contact the individual showroom for product, trade or appointment enquiries.</p>'), 'field_link' => $link('/brands', 'Find a brand contact')]),
      $p('info_row', ['field_heading' => 'Venue hire', 'field_text' => $text('<p>Use the Newson’s Yard enquiry form for space hire.</p>'), 'field_link' => $link('/newsons-yard', 'Enquire about Newson’s Yard')]),
    ],
  ]),
  $p('webform', [
    'field_eyebrow' => 'Contact form',
    'field_heading' => 'Send us an enquiry',
    'field_anchor' => 'enquiry',
    'field_webform' => ['target_id' => 'contact'],
  ]),
  $visit_banner('While you are here', 'Discover the people and places on Pimlico Road.', 'Explore the brands', '/brands', 'Next step'),
]);

// Policies (T16).
$policy = static fn(array $sections) => $p('policy_content', ['field_text' => $text(
  '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim.</p>'
  . implode('', array_map(static fn($h) => "<h2>$h</h2>$lorem<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.</p>", $sections))
  . '<ul><li>Placeholder first supporting item with further detail.</li><li>Placeholder second supporting item with further detail.</li></ul>'
)]);
$related = static fn(array $links) => $p('cta', ['field_heading' => 'Related information', 'field_links' => $links]);
$page('Privacy', '/privacy', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.', [
  $policy(['Who we are', 'Information we collect', 'How we use information', 'Retention and sharing', 'Your choices and contact']),
  $related([$link('/cookies', 'Read the cookies policy'), $link('/contact', 'Contact us')]),
]);
$page('Cookies', '/cookies', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.', [
  $policy(['What cookies are', 'Cookies we use', 'Analytics', 'Managing your preferences', 'Contact']),
  $related([$link('/privacy', 'Read the privacy policy'), $link('/contact', 'Contact us')]),
]);
$page('Accessibility', '/accessibility', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod.', [
  $policy(['Our commitment', 'How accessible this website is', 'Reporting problems', 'Technical information', 'Visiting the street']),
  $p('cta', [
    'field_eyebrow' => 'Visiting information',
    'field_heading' => 'Planning an accessible visit',
    'field_text' => $text('<p>Physical access varies by showroom and venue.</p>'),
    'field_links' => [$link('/visit', 'See practical visit information'), $link('/brands', 'Check a brand’s access information'), $link('/contact', 'Contact us for assistance')],
  ]),
]);
