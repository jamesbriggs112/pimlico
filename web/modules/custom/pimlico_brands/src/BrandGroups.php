<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\TermInterface;

/**
 * The Brands page's category filters, as in Figma (T02).
 *
 * Each filter is a top-level Brand Category (Interiors, Antiques & art) and
 * its sub-categories, if it ever has any. Food & Drink is on Dine and
 * belongs to none. Keys are the ?category= values in the URL.
 */
final class BrandGroups {

  use StringTranslationTrait;

  /**
   * Group key => top-level Brand Category names, in filter order.
   */
  private const GROUPS = [
    'interiors' => ['Interiors'],
    'antiques-and-art' => ['Antiques & art'],
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns the groups: key => label.
   *
   * @return array<string, string>
   *   Labels, in filter order.
   */
  public function labels(): array {
    return [
      'interiors' => (string) $this->t('Interiors'),
      'antiques-and-art' => (string) $this->t('Antiques & art'),
    ];
  }

  /**
   * Returns the term IDs in a group, including their sub-categories.
   *
   * @return int[]
   *   Term IDs, or an empty array for an unknown group.
   */
  public function termIds(string $group): array {
    $ids = [];
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach (self::GROUPS[$group] ?? [] as $name) {
      foreach ($storage->loadByProperties(['vid' => 'brand_category', 'name' => $name]) as $term) {
        $ids[] = (int) $term->id();
        foreach ($storage->loadTree('brand_category', (int) $term->id()) as $child) {
          $ids[] = (int) $child->tid;
        }
      }
    }
    return array_values(array_unique($ids));
  }

  /**
   * Returns the group key a Brand Category belongs to, or NULL.
   */
  public function groupFor(?TermInterface $term): ?string {
    if (!$term) {
      return NULL;
    }
    $parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadAllParents($term->id());
    $top = end($parents) ?: $term;
    foreach (self::GROUPS as $key => $names) {
      if (in_array($top->label(), $names, TRUE)) {
        return $key;
      }
    }
    return NULL;
  }

  /**
   * Returns the label a Brand's category is shown with.
   *
   * Its group's name, or the category's own name outside the groups (Food &
   * Drink and its sub-categories).
   */
  public function labelFor(?TermInterface $term): string {
    $group = $this->groupFor($term);
    return $group ? $this->labels()[$group] : ($term?->label() ?? '');
  }

}
