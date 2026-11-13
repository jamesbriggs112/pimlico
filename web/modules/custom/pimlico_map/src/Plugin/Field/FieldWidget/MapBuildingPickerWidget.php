<?php

declare(strict_types=1);

namespace Drupal\pimlico_map\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\pimlico_map\MapBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lets editors set a Unit's Map Buildings by clicking the map.
 *
 * The map is shown once above the inputs, in pick mode. A click fills the
 * input the editor last focused, or the first empty one. Inputs stay visible
 * but read-only, so the chosen ID can be checked but never mistyped.
 */
#[FieldWidget(
  id: 'pimlico_map_building_picker',
  label: new TranslatableMarkup('Map building picker'),
  field_types: ['string'],
)]
final class MapBuildingPickerWidget extends StringTextfieldWidget {

  /**
   * The map render array builder.
   */
  private MapBuilder $mapBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->mapBuilder = $container->get(MapBuilder::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getName() === 'map_buildings';
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#attributes']['readonly'] = 'readonly';
    $element['value']['#attributes']['data-picker-input'] = '';
    $element['value']['#size'] = 32;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state): array {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    $target = Html::getUniqueId('map-building-picker');
    $elements['#prefix'] = '<div id="' . $target . '" class="map-building-picker">';
    $elements['#suffix'] = '</div>';
    $elements['map'] = [
      '#type' => 'container',
      '#weight' => -100,
      '#attributes' => ['class' => ['map-building-picker__map']],
      'help' => [
        '#markup' => '<p>' . $this->t('Click the shopfront on the map to fill the selected box. Hover over a building to see its address.') . '</p>',
      ],
      'map' => $this->mapBuilder->picker($target),
    ];
    return $elements;
  }

}
