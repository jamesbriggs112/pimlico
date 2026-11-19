<?php

/**
 * @file
 * Deploy hooks for Pimlico Admin: one-off changes to the site's pages.
 *
 * These run after configuration import in `drush deploy`, so they can use
 * field values and fields that the same release adds in config.
 */

declare(strict_types=1);

use Drupal\node\NodeInterface;

/**
 * Design round 3: dark sections, Visit without "Find Pimlico Road".
 *
 * Sets the Dark background (new in this release's config) on Visit "Getting
 * here", Contact "Enquiry routes" and About "Area story"; removes the Visit
 * "Find Pimlico Road" band, which the Figma design dropped; and shows Brand
 * intros on Newson's Yard "Resident brands" (a field new in this release).
 * Pages are found by path alias and sections by type, since each page has
 * one section of that type. A page or section that isn't there (renamed or
 * already changed by an editor) is skipped and reported.
 */
function pimlico_admin_deploy_design_round_3_sections(): string {
  $changes = [
    '/visit' => [
      'info_rows' => ['field_background' => 'dark'],
      'info_band' => NULL,
    ],
    '/contact' => ['info_rows' => ['field_background' => 'dark']],
    '/about' => ['split_text' => ['field_background' => 'dark']],
    '/newsons-yard' => ['content_grid' => ['field_show_summaries' => TRUE]],
  ];
  $report = [];
  foreach ($changes as $alias => $sections) {
    $node = _pimlico_admin_page_by_alias($alias);
    if (!$node) {
      $report[] = "$alias: no page, skipped";
      continue;
    }
    $kept = [];
    $done = [];
    foreach ($node->get('field_sections')->referencedEntities() as $section) {
      $bundle = $section->bundle();
      if (!array_key_exists($bundle, $sections)) {
        $kept[] = $section;
        continue;
      }
      if ($sections[$bundle] === NULL) {
        $done[] = "removed $bundle";
        continue;
      }
      foreach ($sections[$bundle] as $field => $value) {
        $section->set($field, $value);
      }
      // Saved here as a new revision: the page's own save only saves a
      // section when its list of sections changes.
      $section->setNewRevision(TRUE);
      $section->save();
      $kept[] = $section;
      $done[] = "updated $bundle";
    }
    if (!$done) {
      $report[] = "$alias: no matching sections, skipped";
      continue;
    }
    $node->set('field_sections', $kept);
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Design round 3: dark sections and section changes (pimlico_admin deploy hook).');
    if ($node->hasField('moderation_state')) {
      $node->set('moderation_state', 'published');
    }
    $node->save();
    $report[] = "$alias: " . implode(', ', $done);
  }
  return implode('; ', $report) . '.';
}

/**
 * Returns the page node at a path alias, or NULL.
 */
function _pimlico_admin_page_by_alias(string $alias): ?NodeInterface {
  $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
  if (!preg_match('#^/node/(\d+)$#', $path, $matches)) {
    return NULL;
  }
  $node = \Drupal::entityTypeManager()->getStorage('node')->load($matches[1]);
  return $node instanceof NodeInterface && $node->bundle() === 'page' ? $node : NULL;
}
