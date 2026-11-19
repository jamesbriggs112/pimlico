<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * The top-level Brand Categories, and the Brands page filters built on them.
 *
 * Every top-level category except Food & Drink is a Brands page filter
 * (Figma T02), in term weight order, with its sub-categories if it has any.
 * Editors add, rename and reorder them in the vocabulary. Food & Drink and
 * its sub-categories are on Dine instead.
 *
 * Food & Drink is found by UUID: Structure Sync keeps term UUIDs the same on
 * every environment, while term IDs differ and editors can rename terms.
 * Filters are keyed by term ID, which survives a rename; Dine does the same.
 *
 * The street map filters by the same categories (mapCategories()), so the
 * map and the listings can't drift apart.
 */
final class BrandGroups {

  public const FOOD_AND_DRINK = '0d9117e8-f4ea-4d7d-81b2-86a2821cf0ab';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns the Brands page filters: the top-level categories but Dine's.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Terms keyed by term ID, in weight order.
   */
  public function filters(): array {
    $filters = [];
    $tree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('brand_category', 0, 1, TRUE);
    foreach ($tree as $term) {
      if ($term->uuid() !== self::FOOD_AND_DRINK) {
        $filters[(int) $term->id()] = $term;
      }
    }
    return $filters;
  }

  /**
   * Returns the filters' labels.
   *
   * @return array<int, string>
   *   Category names keyed by term ID, in filter order.
   */
  public function labels(): array {
    return array_map(static fn(TermInterface $term) => (string) $term->label(), $this->filters());
  }

  /**
   * Returns the term IDs a filter shows, including its sub-categories.
   *
   * @param int|string $filter
   *   The filter's term ID, as given in ?category=.
   *
   * @return int[]
   *   Term IDs, or an empty array when it isn't a Brands filter.
   */
  public function termIds(int|string $filter): array {
    $filters = $this->filters();
    $tid = is_int($filter) || ctype_digit($filter) ? (int) $filter : 0;
    return isset($filters[$tid]) ? $this->withChildren($filters[$tid]) : [];
  }

  /**
   * Returns the term IDs "All" shows: every filter and its sub-categories.
   *
   * @return int[]
   *   Term IDs.
   */
  public function allTermIds(): array {
    $tids = [];
    foreach ($this->filters() as $term) {
      array_push($tids, ...$this->withChildren($term));
    }
    return $tids;
  }

  /**
   * Returns the Food & Drink category, whose Brands are on Dine.
   */
  public function dineCategory(): ?TermInterface {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'brand_category', 'uuid' => self::FOOD_AND_DRINK]);
    return reset($terms) ?: NULL;
  }

  /**
   * Returns the term IDs on Dine: Food & Drink first, then its sub-categories.
   *
   * @return int[]
   *   Term IDs.
   */
  public function dineTermIds(): array {
    $food = $this->dineCategory();
    return $food ? $this->withChildren($food) : [];
  }

  /**
   * Returns the Dine page filters: Food & Drink's sub-categories.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Terms keyed by term ID, in weight order.
   */
  public function dineFilters(): array {
    $food = $this->dineCategory();
    $filters = [];
    $tree = $food ? $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('brand_category', (int) $food->id(), 1, TRUE) : [];
    foreach ($tree as $term) {
      $filters[(int) $term->id()] = $term;
    }
    return $filters;
  }

  /**
   * Returns the street map's categories: the Brands filters, then Dine's.
   *
   * Food & Drink itself is never one: its Brands are split by sub-category,
   * as on Dine. Cache the result with the taxonomy_term_list:brand_category
   * tag, so terms editors add, rename or reorder show straight away.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Terms keyed by term ID, in filter order.
   */
  public function mapCategories(): array {
    return $this->filters() + $this->dineFilters();
  }

  /**
   * Returns the street map category a Brand Category belongs to.
   *
   * A Brands category or anything below it belongs to the top-level term
   * (Lamps to Lighting); a Dine tag, or anything below it, to its Food &
   * Drink sub-category (Cafés). Food & Drink itself, a heading editors
   * can't choose, belongs to none, so its Brands show only under "All".
   */
  public function mapCategoryFor(?TermInterface $term): ?TermInterface {
    if ($term === NULL) {
      return NULL;
    }
    // The term first, its top-level category last.
    $lineage = array_values($this->entityTypeManager->getStorage('taxonomy_term')->loadAllParents($term->id())) ?: [$term];
    $top = end($lineage);
    if ($top->uuid() !== self::FOOD_AND_DRINK) {
      return $top;
    }
    return $lineage[count($lineage) - 2] ?? NULL;
  }

  /**
   * Whether a Brand Category is Food & Drink or one of its sub-categories.
   */
  public function isDine(?TermInterface $term): bool {
    return $term !== NULL && $this->topCategory($term)->uuid() === self::FOOD_AND_DRINK;
  }

  /**
   * Returns the Brands filter a Brand Category belongs to, or NULL on Dine.
   */
  public function filterFor(?TermInterface $term): ?TermInterface {
    return $term && !$this->isDine($term) ? $this->topCategory($term) : NULL;
  }

  /**
   * Returns the label a Brand's category is shown with on its card.
   *
   * The Brand's own category, its most specific tag: "Cafés" or "Pubs and
   * restaurants" for a Dine Brand rather than Food & Drink, and the
   * top-level name ("Interiors") only for a Brand filed there directly.
   * Editors add tags as terms in the vocabulary, so a new one shows here
   * as soon as a Brand uses it.
   */
  public function labelFor(?TermInterface $term): string {
    return $term ? (string) $term->label() : '';
  }

  /**
   * Returns a Brand Category's top-level category, or the term itself.
   */
  public function topCategory(TermInterface $term): TermInterface {
    $parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadAllParents($term->id());
    return end($parents) ?: $term;
  }

  /**
   * Returns a term's ID followed by those of every term below it.
   *
   * @return int[]
   *   Term IDs.
   */
  private function withChildren(TermInterface $term): array {
    $children = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('brand_category', (int) $term->id());
    return [(int) $term->id(), ...array_map(static fn($child) => (int) $child->tid, $children)];
  }

}
