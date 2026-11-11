<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\pimlico_brands\BrandGroups;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filters the Brands page by category group (/brands?category=interiors).
 *
 * The group's term IDs go to the page_brands display's contextual filter
 * ("1+3+4").
 */
final class BrandsViewHooks {

  public function __construct(
    private readonly BrandGroups $groups,
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * Implements hook_views_pre_view().
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view, string $display_id, array &$args): void {
    if ($view->id() !== 'brands' || $display_id !== 'page_brands') {
      return;
    }
    $request = $this->requestStack->getCurrentRequest();
    $group = (string) $request?->query->get('category', '');
    // "All" is every grouped category, which leaves out Food & Drink (Dine).
    $keys = isset($this->groups->labels()[$group]) ? [$group] : array_keys($this->groups->labels());
    $tids = [];
    foreach ($keys as $key) {
      array_push($tids, ...$this->groups->termIds($key));
    }
    // A contextual filter, not the exposed one: pager links keep the URL's
    // ?category=interiors instead of an array of term IDs.
    $args = [$tids ? implode('+', $tids) : '0'];
  }

}
