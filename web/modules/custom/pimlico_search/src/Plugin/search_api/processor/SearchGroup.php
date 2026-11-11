<?php

declare(strict_types=1);

namespace Drupal\pimlico_search\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the result group a node belongs to on the search page.
 *
 * Brands in Food & Drink (or one of its child categories) are "dine", other
 * Brands are "brands"; Articles and Podcast Episodes are "news" and Press
 * Articles are "press".
 */
#[SearchApiProcessor(
  id: 'pimlico_search_group',
  label: new TranslatableMarkup('Search group'),
  description: new TranslatableMarkup('The result group (brands, dine, news, press) for the search page.'),
  stages: ['add_properties' => 0],
  locked: TRUE,
  hidden: TRUE,
)]
final class SearchGroup extends ProcessorPluginBase {

  /**
   * The Brand Category whose Brands are places to eat and drink.
   */
  public const DINE_CATEGORY = 'Food & Drink';

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    if ($datasource) {
      return [];
    }
    return [
      'pimlico_search_group' => new ProcessorProperty([
        'label' => $this->t('Search group'),
        'description' => $this->t('brands, dine, news or press.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item): void {
    $node = $item->getOriginalObject()->getValue();
    if (!$node instanceof NodeInterface) {
      return;
    }
    $group = self::groupFor($node);
    if ($group === NULL) {
      return;
    }
    $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'pimlico_search_group');
    foreach ($fields as $field) {
      $field->addValue($group);
    }
  }

  /**
   * Returns the search group for a node, or NULL when it has none.
   */
  public static function groupFor(NodeInterface $node): ?string {
    return match ($node->bundle()) {
      'brand' => self::isDine($node) ? 'dine' : 'brands',
      'article', 'podcast_episode' => 'news',
      'press_article' => 'press',
      default => NULL,
    };
  }

  /**
   * Whether a Brand's category is Food & Drink or one of its children.
   */
  private static function isDine(NodeInterface $node): bool {
    if (!$node->hasField('field_brand_category') || $node->get('field_brand_category')->isEmpty()) {
      return FALSE;
    }
    $term = $node->get('field_brand_category')->entity;
    if (!$term) {
      return FALSE;
    }
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    foreach ($storage->loadAllParents($term->id()) as $ancestor) {
      if ($ancestor->label() === self::DINE_CATEGORY) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
