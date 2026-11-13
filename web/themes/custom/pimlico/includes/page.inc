<?php

/**
 * @file
 * Theme and preprocess functions for pages.
 */

declare(strict_types=1);

/**
 * Implements hook_preprocess_HOOK() for page.
 *
 * Views pages (Brands, Dine, News) lay out their own full-width sections,
 * like node pages, rather than sitting in the content column.
 */
function pimlico_preprocess_page(array &$variables): void {
  $variables['is_view_page'] = (bool) \Drupal::routeMatch()->getParameter('view_id');
}
