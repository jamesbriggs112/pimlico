<?php

declare(strict_types=1);

namespace Drupal\pimlico_map\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\pimlico_map\MapBuilder;

/**
 * The map's theme hook and the "Showroom map" on Brand pages.
 */
final class MapHooks {

  use StringTranslationTrait;

  public function __construct(
    private readonly MapBuilder $mapBuilder,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'pimlico_map' => [
        'variables' => [
          'src' => NULL,
          'feed' => NULL,
          'mode' => 'overview',
          'brand' => NULL,
          'category' => NULL,
          'locate' => FALSE,
          'pick' => FALSE,
          'map_id' => 'pimlico-map',
          'attributes' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    return [
      'node' => [
        'brand' => [
          'display' => [
            'showroom_map' => [
              'label' => $this->t('Showroom map'),
              'description' => $this->t('The street map zoomed to the Brand, when it is active and its Unit is placed.'),
              'weight' => 20,
              'visible' => FALSE,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_view() for node.
   */
  #[Hook('node_view')]
  public function nodeView(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, string $view_mode): void {
    if ($node->bundle() === 'brand' && $display->getComponent('showroom_map')) {
      $build['showroom_map'] = $this->mapBuilder->brand($node);
    }
  }

}
