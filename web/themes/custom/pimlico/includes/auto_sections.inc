<?php

/**
 * @file
 * Preprocess for the auto sections and the pages they close.
 *
 * Similar brands (Brands) and Related (Stories) are sections that fill
 * themselves from the node they're on (see pimlico_sections' AutoSections).
 * They are kept apart from paragraph.inc because they read their host node,
 * which ordinary sections never do.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pimlico_sections\AutoSections;

/**
 * Implements hook_preprocess_HOOK() for paragraph--similar-brands.
 *
 * Adds host: the Brand's picked Similar brands, rendered as the Brand page
 * renders them, or the category and node ID the brands:block_similar
 * fallback takes. The template can't get these itself: the section only
 * knows its parent through getParentEntity(), and reaching through it in
 * Twig would skip the field's access check and the Brand's cache tags.
 */
function pimlico_preprocess_paragraph__similar_brands(array &$variables): void {
  $brand = _pimlico_auto_section_host($variables, ['brand']);
  if (!$brand) {
    return;
  }
  $category = $brand->get('field_brand_category')->entity;
  $variables['host'] = [
    'id' => $brand->id(),
    'category' => $category ? $category->id() : 'all',
    'picks' => _pimlico_auto_section_picks($brand, 'field_similar_brands'),
  ];
  if ($category) {
    CacheableMetadata::createFromRenderArray($variables)
      ->addCacheableDependency($category)
      ->applyTo($variables);
  }
}

/**
 * Implements hook_preprocess_HOOK() for paragraph--related.
 *
 * Adds host: the Story's type, its picked Related stories or brands,
 * rendered as the Story page renders them, and the news category and node
 * ID the fallback listings take. See the Similar brands preprocess for why
 * this can't be done in Twig.
 */
function pimlico_preprocess_paragraph__related(array &$variables): void {
  $story = _pimlico_auto_section_host($variables, ['article', 'press_article']);
  if (!$story) {
    return;
  }
  $variables['host'] = [
    'bundle' => $story->bundle(),
    'id' => $story->id(),
    'category' => $story->get('field_news_category')->target_id ?? 'all',
    'picks' => _pimlico_auto_section_picks($story, 'field_related'),
  ];
}

/**
 * Returns the node an auto section is on, if it's one of the given bundles.
 *
 * Adds the node's cache metadata to the section, since what the section
 * shows changes with the node's picks and category. A section anywhere
 * else (another content type, or not yet saved to a node) gets no host, and
 * its template prints nothing.
 */
function _pimlico_auto_section_host(array &$variables, array $bundles): ?NodeInterface {
  $variables['host'] = NULL;
  $host = $variables['paragraph']->getParentEntity();
  if (!$host instanceof NodeInterface || !in_array($host->bundle(), $bundles, TRUE)) {
    return NULL;
  }
  CacheableMetadata::createFromRenderArray($variables)
    ->addCacheableDependency($host)
    ->applyTo($variables);
  return $host;
}

/**
 * Renders a node's picks field with its full display, or NULL when empty.
 *
 * The node's display config chooses the formatter (teaser cards) and the
 * render array carries the field access check and the cards' cache tags.
 */
function _pimlico_auto_section_picks(NodeInterface $node, string $field_name): ?array {
  $items = $node->get($field_name);
  return $items->isEmpty() ? NULL : $items->view('full');
}

/**
 * Implements hook_preprocess_HOOK() for node--brand--full.
 */
function pimlico_preprocess_node__brand__full(array &$variables): void {
  _pimlico_closing_sections($variables);
}

/**
 * Implements hook_preprocess_HOOK() for node--article--full.
 */
function pimlico_preprocess_node__article__full(array &$variables): void {
  _pimlico_closing_sections($variables);
}

/**
 * Implements hook_preprocess_HOOK() for node--press-article--full.
 */
function pimlico_preprocess_node__press_article__full(array &$variables): void {
  _pimlico_closing_sections($variables);
}

/**
 * Moves the auto sections that end a page into closing_sections.
 *
 * The design puts Similar brands below "Find the showroom" and Related
 * below a Story's credits, both fixed parts of the page template. So an auto
 * section at the end of the sections closes the page, after those parts;
 * one an editor has moved up the page stays where it was put. Only the
 * trailing run moves, so the sections field keeps its deltas from 0 (core's
 * field preprocess stops at the first missing delta).
 */
function _pimlico_closing_sections(array &$variables): void {
  $variables['closing_sections'] = [];
  if (empty($variables['content']['field_sections'])) {
    return;
  }
  $sections = &$variables['content']['field_sections'];
  $auto = \Drupal::service(AutoSections::class);
  $deltas = Element::children($sections);
  $closing = [];
  while ($deltas) {
    $delta = array_pop($deltas);
    $paragraph = $sections[$delta]['#paragraph'] ?? NULL;
    if (!$paragraph instanceof ParagraphInterface || !$auto->isAutoSection($paragraph)) {
      break;
    }
    array_unshift($closing, $sections[$delta]);
    unset($sections[$delta]);
  }
  $variables['closing_sections'] = $closing;
}
