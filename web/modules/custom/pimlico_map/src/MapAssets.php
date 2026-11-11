<?php

declare(strict_types=1);

namespace Drupal\pimlico_map;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;

/**
 * Builds the two URLs the map component reads.
 *
 * The base map is a static file with a one-year expiry behind CloudFront, so
 * its URL carries the library version: copying in a new build and bumping
 * the version is what gets browsers the new map.
 */
final class MapAssets {

  public function __construct(
    private readonly ModuleExtensionList $moduleList,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly LibraryDiscoveryInterface $libraryDiscovery,
  ) {}

  /**
   * Returns the base map URL, versioned.
   */
  public function baseMapUrl(): string {
    $path = $this->moduleList->getPath('pimlico_map') . '/assets/pimlico-map.json';
    return $this->fileUrlGenerator->generateString($path) . '?v=' . $this->version();
  }

  /**
   * Returns the tenancy feed URL.
   */
  public function feedUrl(): string {
    return Url::fromRoute('pimlico_map.brand_feed')->toString();
  }

  /**
   * Returns the map component's version, from the library definition.
   */
  public function version(): string {
    $library = $this->libraryDiscovery->getLibraryByName('pimlico_map', 'pimlico-map');
    return (string) ($library['version'] ?? '0');
  }

}
