<?php

/**
 * @file
 * Imports each Brand's photos listed in data/photos.json into the site.
 *
 *   Drush php:script modules/custom/pimlico_migrate/scripts/import-photos.php.
 *
 * Downloads each photo (a 1920px WebP from belgraviavillage.com), saves it as
 * an 82% JPEG under public://brands, and creates an Image media item named
 * "Brand photo: <Brand> <n>". The first photo becomes the Brand's main image;
 * with two or more, they replace the gallery. A Brand with one photo has its
 * gallery cleared rather than showing sample photos of other places. Re-run
 * safely: existing media are reused. Alt text is the Brand's name; editors
 * should describe each photo.
 */

declare(strict_types=1);

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;

$data = json_decode((string) file_get_contents(dirname(__DIR__) . '/data/photos.json'), TRUE, flags: JSON_THROW_ON_ERROR)['brands'];
$brands = json_decode((string) file_get_contents(dirname(__DIR__) . '/data/brands.json'), TRUE, flags: JSON_THROW_ON_ERROR)['brands'];
$names = array_column($brands, 'name', 'id');

$etm = \Drupal::entityTypeManager();
$media_storage = $etm->getStorage('media');
$nodes = $etm->getStorage('node');
$fs = \Drupal::service('file_system');
$repository = \Drupal::service('file.repository');
$dir = 'public://brands';
$fs->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
$context = stream_context_create([
  'http' => [
    'header' => "User-Agent: Mozilla/5.0 (compatible; pimlico-map/1.0)\r\n",
    'timeout' => 30,
  ],
]);

$created = $updated = 0;
foreach ($data as $id => $entry) {
  $name = $names[$id] ?? NULL;
  $brand = $name ? current($nodes->loadByProperties(['type' => 'brand', 'title' => $name])) : NULL;
  if (!$brand instanceof NodeInterface || !$entry['photos']) {
    continue;
  }

  $media = [];
  foreach ($entry['photos'] as $n => $url) {
    $label = sprintf('Brand photo: %s %d', $name, $n + 1);
    $existing = current($media_storage->loadByProperties(['bundle' => 'image', 'name' => $label]));
    if ($existing) {
      $media[] = $existing;
      continue;
    }
    $bytes = @file_get_contents($url, FALSE, $context);
    usleep(300000);
    $image = $bytes ? @imagecreatefromstring($bytes) : FALSE;
    if (!$image) {
      fwrite(STDERR, "$id: could not read $url\n");
      continue;
    }
    ob_start();
    imagejpeg($image, NULL, 82);
    $jpeg = (string) ob_get_clean();
    imagedestroy($image);

    $file = $repository->writeData($jpeg, sprintf('%s/%s-%d.jpg', $dir, $id, $n + 1), FileExists::Replace);
    $item = Media::create([
      'bundle' => 'image',
      'name' => $label,
      'uid' => 1,
      'field_media_image' => ['target_id' => $file->id(), 'alt' => $name],
    ]);
    $item->save();
    $media[] = $item;
    $created++;
  }
  if (!$media) {
    continue;
  }

  $brand->set('field_image', $media[0]);
  $brand->set('field_gallery', count($media) > 1 ? $media : []);
  if (count($media) > 1 && $brand->get('field_caption')->value === 'Photography: sample images') {
    $brand->set('field_caption', NULL);
  }
  $brand->setNewRevision(TRUE);
  $brand->setRevisionLogMessage('Photos from belgraviavillage.com (pimlico_migrate/scripts/import-photos.php).');
  $brand->setSyncing(TRUE);
  $brand->save();
  $updated++;
}
echo "$created photos imported; $updated Brands updated.\n";
