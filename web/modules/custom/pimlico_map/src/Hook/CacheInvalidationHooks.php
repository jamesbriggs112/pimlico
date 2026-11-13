<?php

declare(strict_types=1);

namespace Drupal\pimlico_map\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Purges the Tenancy Feed from CloudFront when a Brand or Unit changes.
 *
 * Drupal's own caches clear by tag; CloudFront can't, so the feed path is
 * invalidated by URL, the same way the skeleton's cloudfront_cache_home_page
 * purges "/". Nothing happens where the CloudFront module isn't enabled
 * (locally).
 */
final class CacheInvalidationHooks {

  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    #[Autowire(service: 'logger.channel.pimlico_map')]
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    $this->purge($entity);
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    $this->purge($entity);
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    $this->purge($entity);
  }

  /**
   * Invalidates the feed URL when the entity is on the map.
   */
  private function purge(EntityInterface $entity): void {
    $on_map = $entity->getEntityTypeId() === 'pimlico_unit'
      || ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'brand');
    if (!$on_map || !$this->moduleHandler->moduleExists('cloudfront_cache_path_invalidate')) {
      return;
    }
    $path = Url::fromRoute('pimlico_map.brand_feed')->toString();
    cloudfront_cache_path_invalidate_url([$path]);
    $this->logger->info('Invalidated @path in CloudFront after @type @id changed.', [
      '@path' => $path,
      '@type' => $entity->getEntityTypeId(),
      '@id' => $entity->id(),
    ]);
  }

}
