<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * The top-level Brand Categories, and the Brands page filters built on them.
 *
 * Interiors and Antiques & art are the Brands page filters (Figma T02), with
 * their sub-categories, if they ever have any. Food & Drink and its
 * sub-categories are on Dine instead.
 *
 * Categories are found by UUID: Structure Sync keeps term UUIDs the same on
 * every environment, while term IDs differ and editors can rename terms.
 */
final class BrandGroups {

  public const INTERIORS = 'c233084f-986d-4f08-99a7-d364b2d2905d';
  public const ANTIQUES_AND_ART = '6fcf4b16-a3ad-4a60-95cd-26f38938693b';
  public const FOOD_AND_DRINK = '0d9117e8-f4ea-4d7d-81b2-86a2821cf0ab';

  /**
   * The Brands page filters: ?category= value => category UUID, in order.
   */
  private const GROUPS = [
    'interiors' => self::INTERIORS,
    'antiques-and-art' => self::ANTIQUES_AND_ART,
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns the groups: key => label, the category's name.
   *
   * @return array<string, string>
   *   Labels, in filter order.
   */
  public function labels(): array {
    $labels = [];
    foreach (self::GROUPS as $key => $uuid) {
      if ($term = $this->load($uuid)) {
        $labels[$key] = $term->label();
      }
    }
    return $labels;
  }

  /**
   * Returns the term IDs in a group, including their sub-categories.
   *
   * @return int[]
   *   Term IDs, or an empty array for an unknown group.
   */
  public function termIds(string $group): array {
    $term = isset(self::GROUPS[$group]) ? $this->load(self::GROUPS[$group]) : NULL;
    return $term ? $this->withChildren($term) : [];
  }

  /**
   * Returns the Food & Drink category, whose Brands are on Dine.
   */
  public function dineCategory(): ?TermInterface {
    return $this->load(self::FOOD_AND_DRINK);
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
   * Whether a Brand Category is Food & Drink or one of its sub-categories.
   */
  public function isDine(?TermInterface $term): bool {
    return $term !== NULL && $this->topCategory($term)->uuid() === self::FOOD_AND_DRINK;
  }

  /**
   * Returns the group key a Brand Category belongs to, or NULL.
   */
  public function groupFor(?TermInterface $term): ?string {
    if (!$term) {
      return NULL;
    }
    $key = array_search($this->topCategory($term)->uuid(), self::GROUPS, TRUE);
    return $key === FALSE ? NULL : $key;
  }

  /**
   * Returns the label a Brand's category is shown with.
   *
   * Its group's name, or the category's own name outside the groups (Food &
   * Drink and its sub-categories).
   */
  public function labelFor(?TermInterface $term): string {
    if (!$term) {
      return '';
    }
    return $this->groupFor($term) ? $this->topCategory($term)->label() : $term->label();
  }

  /**
   * Returns a Brand Category's top-level category, or the term itself.
   */
  public function topCategory(TermInterface $term): TermInterface {
    $parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadAllParents($term->id());
    return end($parents) ?: $term;
  }

  /**
   * Loads a Brand Category by UUID.
   */
  private function load(string $uuid): ?TermInterface {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'brand_category', 'uuid' => $uuid]);
    return reset($terms) ?: NULL;
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
