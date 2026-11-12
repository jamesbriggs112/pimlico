<?php

/**
 * @file
 * Post-update functions for Pimlico Admin.
 */

declare(strict_types=1);

/**
 * Deletes the skeleton's "test" webform, which config import ignores.
 */
function pimlico_admin_post_update_delete_test_webform(): string {
  if (!\Drupal::moduleHandler()->moduleExists('webform')) {
    return 'Webform is not installed.';
  }
  $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('test');
  if (!$webform) {
    return 'No test webform.';
  }
  $webform->delete();
  return 'Deleted the test webform and its submissions.';
}
