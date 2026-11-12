<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\pimlico_brands\BrandGroups;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filters Brands and Dine by category through a contextual filter.
 *
 * Brands: /brands?category=interiors, the group's term IDs ("1+3+4").
 * Dine: /dine?category=17, one Food & Drink sub-category, or all of Food &
 * Drink. A contextual filter rather than exposed ones: pager links keep the
 * URL's ?category= value, and two filters on the same field would join in a
 * way that drops results.
 */
final class BrandsViewHooks {

  public function __construct(
    private readonly BrandGroups $groups,
    private readonly RequestStack $requestStack,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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
    $group = (string) $request?->query->get('category', '');
    if ($display_id === 'page_dine') {
      $args = [implode('+', $this->dineTermIds($group)) ?: '0'];
      return;
    }
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

  /**
   * Returns Dine's term IDs: one sub-category, or Food & Drink and all below.
   *
   * @return int[]
   *   Term IDs.
   */
  private function dineTermIds(string $category): array {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $food = $storage->loadByProperties(['vid' => 'brand_category', 'name' => 'Food & Drink']);
    $food = reset($food);
    if (!$food) {
      return [];
    }
    $children = array_map(static fn($term) => (int) $term->tid, $storage->loadTree('brand_category', (int) $food->id()));
    if (ctype_digit($category) && in_array((int) $category, $children, TRUE)) {
      return [(int) $category];
    }
    return [(int) $food->id(), ...$children];
  }

}
