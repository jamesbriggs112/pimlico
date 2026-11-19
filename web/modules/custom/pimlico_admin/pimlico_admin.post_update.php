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

/**
 * Deletes every Podcast episode, so the configuration import can remove it.
 *
 * Podcasts were dropped from the build (19 Nov 2026). Configuration import
 * won't delete a content type that still has content, and `drush deploy`
 * runs post-updates before the import, so the episodes go here first.
 * Re-runnable: once there are none, or the content type is already gone,
 * it does nothing.
 */
function pimlico_admin_post_update_delete_podcast_episodes(): string {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  if (!\Drupal::entityTypeManager()->getStorage('node_type')->load('podcast_episode')) {
    return 'No Podcast episode content type.';
  }
  $ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'podcast_episode')
    ->execute();
  if (!$ids) {
    return 'No Podcast episodes.';
  }
  $storage->delete($storage->loadMultiple($ids));
  return sprintf('Deleted %d Podcast episodes.', count($ids));
}
