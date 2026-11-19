<?php

/**
 * @file
 * Attaches the icons in ./icons to their Paragraph types.
 *
 * Each icon is saved as a file and embedded in the type's config as
 * icon_default, so other environments recreate it on config import. Run
 * generate.py first, then:
 *
 *   vendor/bin/drush php:script scripts/dev/paragraph-icons/set-icons.php
 *   vendor/bin/drush cex
 */

declare(strict_types=1);

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\ParagraphsType;

$directory = 'public://paragraphs_type_icon';
$file_system = \Drupal::service('file_system');
$file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

foreach (glob(__DIR__ . '/icons/*.svg') as $path) {
  $id = basename($path, '.svg');
  $type = ParagraphsType::load($id);
  if (!$type) {
    echo "No Paragraph type '$id', skipped.\n";
    continue;
  }

  $svg = file_get_contents($path);
  $uri = $file_system->saveData($svg, "$directory/$id.svg", FileExists::Replace);
  $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
  $file = reset($files) ?: File::create(['uri' => $uri, 'uid' => 1]);
  $file->setPermanent();
  $file->save();

  $type->set('icon_uuid', $file->uuid());
  $type->set('icon_default', 'data:image/svg+xml;base64,' . base64_encode($svg));
  $type->save();
  echo "$id: icon set.\n";
}
