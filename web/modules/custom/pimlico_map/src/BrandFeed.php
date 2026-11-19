<?php

declare(strict_types=1);

namespace Drupal\pimlico_map;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\pimlico_brands\BrandGroups;
use Drupal\pimlico_map\Entity\UnitInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Builds the Tenancy Feed: active Brands and the Map Buildings they light.
 *
 * The shape is fixed by the map component (map guide, B3):
 * {"brands": [{id, name, label?, category, url, address?, buildings, status}]}.
 * Only published, active Brands shown on the map with at least one placed
 * Unit are included. A Brand's category is its street map category
 * (BrandGroups::mapCategoryFor()), the same one the listings filter it by.
 */
final class BrandFeed {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AliasRepositoryInterface $aliasRepository,
    private readonly BrandGroups $brandGroups,
  ) {}

  /**
   * Returns the feed data and its cacheability.
   *
   * @return array{0: array, 1: \Drupal\Core\Cache\CacheableMetadata}
   *   The feed, and the cache metadata to attach to the response.
   */
  public function build(): array {
    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:brand', 'pimlico_unit_list', 'taxonomy_term_list:brand_category']);

    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'brand')
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('field_tenancy_status', 'active')
      ->condition('field_display_on_map', 1)
      ->sort('title')
      ->execute();

    $brands = [];
    foreach ($storage->loadMultiple($ids) as $brand) {
      assert($brand instanceof NodeInterface);
      $item = $this->brandItem($brand, $cache);
      if ($item) {
        $brands[] = $item;
      }
    }
    return [['brands' => $brands], $cache];
  }

  /**
   * Returns a Brand's feed item, or NULL when it has no placed Unit.
   */
  public function brandItem(NodeInterface $brand, CacheableMetadata $cache): ?array {
    $cache->addCacheableDependency($brand);
    $buildings = [];
    $address = '';
    foreach ($brand->get('field_units')->referencedEntities() as $unit) {
      assert($unit instanceof UnitInterface);
      $cache->addCacheableDependency($unit);
      array_push($buildings, ...$unit->getMapBuildings());
      $address = $address ?: $unit->getAddressLine();
    }
    if (!$buildings) {
      return NULL;
    }

    $category = $this->mapCategory($brand, $cache);
    $url = $brand->toUrl()->toString(TRUE);
    $cache->addCacheableDependency($url);

    return array_filter([
      'id' => $this->mapId($brand),
      'name' => $brand->label(),
      'label' => (string) $brand->get('field_map_label')->value,
      'category' => $category?->label() ?? '',
      'url' => $url->getGeneratedUrl(),
      'address' => $address ?: $this->brandAddress($brand),
      'buildings' => array_values(array_unique($buildings)),
      'status' => 'active',
    ], static fn($value) => $value !== '' && $value !== NULL);
  }

  /**
   * Whether a Brand appears on the map (and so has a map on its page).
   */
  public function isOnMap(NodeInterface $brand): bool {
    if ($brand->bundle() !== 'brand' || !$brand->isPublished()) {
      return FALSE;
    }
    if ($brand->get('field_tenancy_status')->value !== 'active' || !$brand->get('field_display_on_map')->value) {
      return FALSE;
    }
    foreach ($brand->get('field_units')->referencedEntities() as $unit) {
      if ($unit instanceof UnitInterface && $unit->getMapBuildings()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns the Brand's map ID: the last segment of its path alias.
   *
   * The Brand page passes the same ID as brand="", so the two always match.
   */
  public function mapId(NodeInterface $brand): string {
    $path = '/node/' . $brand->id();
    $alias = $this->aliasRepository->lookupBySystemPath($path, $brand->language()->getId());
    return basename($alias['alias'] ?? $path);
  }

  /**
   * Returns the Brand's street map category, which the map filters by.
   *
   * Brands sub-categories roll up to their top-level term, and Dine Brands
   * keep their Food & Drink sub-category (Cafés), matching the filter bar.
   */
  private function mapCategory(NodeInterface $brand, CacheableMetadata $cache): ?TermInterface {
    $term = $brand->get('field_brand_category')->entity;
    $category = $this->brandGroups->mapCategoryFor($term instanceof TermInterface ? $term : NULL);
    if ($category) {
      $cache->addCacheableDependency($category);
    }
    return $category;
  }

  /**
   * Returns the Brand's own address on one line, for Units without one.
   */
  private function brandAddress(NodeInterface $brand): string {
    if (!$brand->hasField('field_address') || $brand->get('field_address')->isEmpty()) {
      return '';
    }
    $address = $brand->get('field_address')->first();
    return implode(', ', array_filter([
      $address->getAddressLine1(),
      $address->getLocality(),
      $address->getPostalCode(),
    ]));
  }

}
