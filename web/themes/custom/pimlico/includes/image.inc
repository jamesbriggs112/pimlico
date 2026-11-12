<?php

/**
 * @file
 * Functions to support theming.
 */

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\crop\Entity\Crop;

/**
 * Implements hook_preprocess_image_widget().
 */
function pimlico_preprocess_image_widget(array &$variables) {
  $data = &$variables['data'];

  // This prevents image widget templates from rendering preview container HTML
  // to users that do not have permission to access these previews.
  // @todo revisit in https://drupal.org/node/953034
  // @todo revisit in https://drupal.org/node/3114318
  if (isset($data['preview']['#access']) && $data['preview']['#access'] === FALSE) {
    unset($data['preview']);
  }
}

/**
 * Implements hook_preprocess_responsive_image().
 *
 * Most images are served whole and cropped by the browser (object-fit:
 * cover) to each layout's shape. The editor's focal point becomes the
 * --focal-point custom property, which object-position uses, so that part
 * of the photo stays in view.
 */
function pimlico_preprocess_responsive_image(array &$variables): void {
  _pimlico_focal_point_position($variables);
}

/**
 * Implements hook_preprocess_image().
 */
function pimlico_preprocess_image(array &$variables): void {
  // Only whole, scaled images: a focal-point-cropped style is already framed.
  if (empty($variables['style_name']) || str_starts_with((string) $variables['style_name'], 'sc_')) {
    _pimlico_focal_point_position($variables);
  }
}

/**
 * Sets --focal-point from the image's focal point, if one is set.
 */
function _pimlico_focal_point_position(array &$variables): void {
  $uri = $variables['uri'] ?? NULL;
  $width = (int) ($variables['width'] ?? 0);
  $height = (int) ($variables['height'] ?? 0);
  if (!$uri || !$width || !$height || !\Drupal::moduleHandler()->moduleExists('focal_point')) {
    return;
  }
  $crop = Crop::findCrop($uri, 'focal_point');
  if (!$crop) {
    return;
  }
  $position = $crop->position();
  $x = max(0, min(100, round($position['x'] / $width * 100, 1)));
  $y = max(0, min(100, round($position['y'] / $height * 100, 1)));
  // Responsive images carry their <img> attributes in img_element.
  if (isset($variables['img_element']['#attributes'])) {
    $attributes = &$variables['img_element']['#attributes'];
  }
  else {
    $attributes = &$variables['attributes'];
  }
  $style = trim((string) ($attributes['style'] ?? ''), '; ');
  $attributes['style'] = ($style ? $style . '; ' : '') . "--focal-point: {$x}% {$y}%";
  $cache = CacheableMetadata::createFromRenderArray($variables);
  $cache->addCacheableDependency($crop)->applyTo($variables);
}
