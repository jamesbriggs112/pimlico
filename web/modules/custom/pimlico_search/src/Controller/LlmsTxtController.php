<?php

declare(strict_types=1);

namespace Drupal\pimlico_search\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Serves /llms.txt: a plain summary of the site for AI search and agents.
 *
 * Follows the llms.txt proposal (llmstxt.org): a title, a one-line summary,
 * then sections of links with short descriptions. Built from published
 * content and cached by tag, so it is always current.
 */
final class LlmsTxtController implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns the text.
   */
  public function __invoke(): CacheableResponse {
    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list', 'config:system.site'])
      ->addCacheContexts(['url.site']);
    $site = $this->configFactory->get('system.site');
    $cache->addCacheableDependency($site);
    $url = static fn(string $route, array $params = []) => Url::fromRoute($route, $params, ['absolute' => TRUE])->toString();

    $lines = [
      '# ' . $site->get('name'),
      '',
      '> Pimlico Road is a street in Belgravia, London, known for antique dealers, furniture makers, fabric houses, lighting and interior designers, with Newson’s Yard (a former timber yard reopened to makers) and places to eat and drink. This site lists every brand on the street with its address, opening hours and location on a map, and publishes news, podcasts and press coverage.',
      '',
      '## Main pages',
      '',
      '- [Brands](' . $url('view.brands.page_brands') . '): every shop, showroom and dealer on the street, by category',
      '- [Dine](' . $url('view.brands.page_dine') . '): restaurants, pubs and cafés',
      '- [News](' . $url('view.news.page_news') . '): stories, podcast episodes and press coverage',
      '- [Search](' . $url('view.search.page_search') . '): search brands and stories',
    ];
    foreach (['Visit', 'Newson’s Yard', 'About Pimlico Road', 'Contact'] as $title) {
      if ($page = $this->load('page', $title, $cache)) {
        $lines[] = '- [' . $page->label() . '](' . $page->toUrl('canonical', ['absolute' => TRUE])->toString() . ')' . $this->summary($page);
      }
    }
    $lines[] = '- [Street map data](' . $url('pimlico_map.brand_feed') . '): JSON list of active brands, their categories, addresses and pages';

    $lines[] = '';
    $lines[] = '## Brands';
    $lines[] = '';
    foreach ($this->published('brand', 'title', 'ASC', 200, $cache) as $brand) {
      $category = $brand->get('field_brand_category')->entity;
      $lines[] = '- [' . $brand->label() . '](' . $brand->toUrl('canonical', ['absolute' => TRUE])->toString() . ')'
        . ($category ? ' (' . $category->label() . ')' : '') . $this->summary($brand);
    }

    $lines[] = '';
    $lines[] = '## Latest stories';
    $lines[] = '';
    foreach ($this->published(['article', 'podcast_episode', 'press_article'], 'created', 'DESC', 20, $cache) as $story) {
      $lines[] = '- [' . $story->label() . '](' . $story->toUrl('canonical', ['absolute' => TRUE])->toString() . ')' . $this->summary($story);
    }

    $response = new CacheableResponse(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    return $response->addCacheableDependency($cache);
  }

  /**
   * Loads published nodes of the given type(s).
   *
   * @return \Drupal\node\NodeInterface[]
   *   The nodes.
   */
  private function published(string|array $types, string $sort, string $direction, int $limit, CacheableMetadata $cache): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', (array) $types, 'IN')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort($sort, $direction)
      ->range(0, $limit)
      ->execute();
    $nodes = $storage->loadMultiple($ids);
    foreach ($nodes as $node) {
      $cache->addCacheableDependency($node);
    }
    return $nodes;
  }

  /**
   * Loads a published page by title.
   */
  private function load(string $type, string $title, CacheableMetadata $cache): ?NodeInterface {
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => $type, 'title' => $title, 'status' => 1]);
    $node = reset($nodes) ?: NULL;
    if ($node) {
      $cache->addCacheableDependency($node);
    }
    return $node;
  }

  /**
   * Returns ": <summary>" for a node, or an empty string.
   */
  private function summary(NodeInterface $node): string {
    $text = $node->hasField('field_summary') ? trim((string) $node->get('field_summary')->value) : '';
    return $text !== '' ? ': ' . preg_replace('/\s+/', ' ', $text) : '';
  }

}
