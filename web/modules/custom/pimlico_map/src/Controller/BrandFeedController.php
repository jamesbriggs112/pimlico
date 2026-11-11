<?php

declare(strict_types=1);

namespace Drupal\pimlico_map\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\pimlico_map\BrandFeed;

/**
 * Serves the Tenancy Feed at /api/map/brands.
 *
 * Cached by tag, so it's current the moment an editor saves a Brand or Unit,
 * and the page cache and CloudFront can both hold it (CloudFront is purged by
 * CacheInvalidationHooks).
 */
final class BrandFeedController implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    private readonly BrandFeed $feed,
  ) {}

  /**
   * Returns the feed.
   */
  public function __invoke(): CacheableJsonResponse {
    [$data, $cache] = $this->feed->build();
    $response = new CacheableJsonResponse($data);
    $response->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $response->addCacheableDependency($cache);
  }

}
