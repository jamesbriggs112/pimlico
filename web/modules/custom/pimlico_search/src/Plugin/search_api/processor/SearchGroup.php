<?php

declare(strict_types=1);

namespace Drupal\pimlico_search\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\pimlico_brands\BrandGroups;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the result group a node belongs to on the search page.
 *
 * Brands in Food & Drink (or one of its child categories) are "dine", other
 * Brands are "brands"; Articles are "news" and Press Articles are
 * "press".
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
   * The Brand Categories, to tell Dine Brands from the rest.
   */
  protected BrandGroups $brandGroups;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->brandGroups = $container->get(BrandGroups::class);
    return $processor;
  }

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
    $group = $this->groupFor($node);
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
  public function groupFor(NodeInterface $node): ?string {
    return match ($node->bundle()) {
      'brand' => $this->brandGroups->isDine($node->get('field_brand_category')->entity) ? 'dine' : 'brands',
      'article' => 'news',
      'press_article' => 'press',
      default => NULL,
    };
  }

}
