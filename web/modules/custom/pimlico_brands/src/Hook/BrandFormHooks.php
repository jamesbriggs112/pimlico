<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The Brand form's category list follows the site's sections.
 *
 * "Brands" lists the top-level categories with no sub-categories
 * (Interiors, Antiques & art); "Dine" lists the sub-categories of Food &
 * Drink (Pubs and restaurants, Cafés). A parent with sub-categories is a
 * heading, not a choice, so every Brand lands on a filter the site shows.
 */
final class BrandFormHooks {

  use StringTranslationTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_field_widget_single_element_options_select_form_alter().
   */
  #[Hook('field_widget_single_element_options_select_form_alter')]
  public function categorySelect(array &$element, FormStateInterface $form_state, array $context): void {
    $definition = $context['items']->getFieldDefinition();
    if ($definition->getName() !== 'field_brand_category' || $definition->getTargetBundle() !== 'brand') {
      return;
    }
    $tree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('brand_category');
    $parents = array_filter(array_map(static fn($term) => (int) ($term->parents[0] ?? 0), $tree));

    $brands = [];
    $groups = [];
    foreach ($tree as $term) {
      $tid = (int) $term->tid;
      $parent = (int) ($term->parents[0] ?? 0);
      if ($parent === 0 && !in_array($tid, $parents, TRUE)) {
        $brands[$tid] = $term->name;
      }
      elseif ($parent !== 0) {
        $groups[$parent][$tid] = $term->name;
      }
    }

    $options = [];
    if (isset($element['#options']['_none'])) {
      $options['_none'] = $element['#options']['_none'];
    }
    if ($brands) {
      $options[(string) $this->t('Brands')] = $brands;
    }
    foreach ($tree as $term) {
      if (isset($groups[(int) $term->tid])) {
        $label = $term->name === 'Food & Drink' ? $this->t('Dine') : $term->name;
        $options[(string) $label] = $groups[(int) $term->tid];
      }
    }
    // Keep a Brand's current category selectable, even a heading one, so an
    // older Brand can still be saved and moved on.
    foreach ((array) ($element['#default_value'] ?? []) as $current) {
      if ($current && isset($element['#options'][$current]) && !$this->inOptions($options, (int) $current)) {
        $options[(int) $current] = ltrim((string) $element['#options'][$current], '-');
      }
    }
    $element['#options'] = $options;
  }

  /**
   * Whether a term ID is among the (grouped) options.
   */
  private function inOptions(array $options, int $tid): bool {
    foreach ($options as $key => $value) {
      if ($key === $tid || (is_array($value) && isset($value[$tid]))) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
