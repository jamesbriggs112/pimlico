<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\pimlico_brands\BrandGroups;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filters Brands and Dine by category through a contextual filter.
 *
 * Brands: /brands?category=1, a top-level category's term ID, which gives it
 * and its sub-categories ("1+3+4"); without one, every Brands filter.
 * Dine: /dine?category=17, one Food & Drink sub-category, or all of Food &
 * Drink. A contextual filter rather than exposed ones: pager links keep the
 * URL's ?category= value, and two filters on the same field would join in a
 * way that drops results.
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
    if ($view->id() !== 'brands' || !in_array($display_id, ['page_brands', 'page_dine'], TRUE)) {
      return;
    }
    $request = $this->requestStack->getCurrentRequest();
    $category = (string) $request?->query->get('category', '');
    if ($display_id === 'page_dine') {
      $args = [implode('+', $this->dineTermIds($category)) ?: '0'];
      return;
    }
    // "All" (or an unknown category) is every Brands filter, which leaves
    // out Food & Drink (Dine).
    $tids = $this->groups->termIds($category) ?: $this->groups->allTermIds();
    $args = [$tids ? implode('+', $tids) : '0'];
  }

  /**
   * Returns Dine's term IDs: one sub-category, or Food & Drink and all below.
   *
   * @return int[]
   *   Term IDs.
   */
  private function dineTermIds(string $category): array {
    $dine = $this->groups->dineTermIds();
    $children = array_slice($dine, 1);
    if (ctype_digit($category) && in_array((int) $category, $children, TRUE)) {
      return [(int) $category];
    }
    return $dine;
  }

}
