<?php

declare(strict_types=1);

namespace Drupal\pimlico_search\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helps search engines and AI crawlers find the right pages.
 */
final class DiscoveryHooks {

  /**
   * Path aliases kept out of the sitemap: the component test pages.
   */
  private const HIDDEN_ALIASES = '#^/component-test#';

  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AliasManagerInterface $aliasManager,
  ) {}

  /**
   * Implements hook_robotstxt().
   *
   * Points crawlers at the sitemap and the llms.txt summary on this host.
   */
  #[Hook('robotstxt')]
  public function robotstxt(): array {
    $host = $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost() ?? '';
    return [
      '',
      'Sitemap: ' . $host . '/sitemap.xml',
    ];
  }

  /**
   * Implements hook_simple_sitemap_links_alter().
   *
   * Drops the Home node (already listed as the front page, "/") and the
   * component test pages.
   */
  #[Hook('simple_sitemap_links_alter')]
  public function sitemapLinksAlter(array &$links): void {
    $front = ltrim((string) $this->configFactory->get('system.site')->get('page.front'), '/');
    foreach ($links as $key => $link) {
      $path = (string) ($link['meta']['path'] ?? '');
      if ($path === '') {
        continue;
      }
      if ($path === $front || preg_match(self::HIDDEN_ALIASES, $this->aliasManager->getAliasByPath('/' . $path))) {
        unset($links[$key]);
      }
    }
  }

}
