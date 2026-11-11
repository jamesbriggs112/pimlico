<?php

declare(strict_types=1);

namespace Drupal\pimlico_map\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\pimlico_map\UnitListBuilder;
use Drupal\views\EntityViewsData;

/**
 * A Unit: a shopfront on the street map that Brands trade from.
 *
 * A Unit outlives the Brands that occupy it, so a Brand leaving never loses
 * the shopfront's place on the map, and a rebuilt base map only means
 * re-pointing Units. It's an editor-only record with no public page, so it's
 * an entity rather than a node or a term (docs/adr/0003).
 */
#[ContentEntityType(
  id: 'pimlico_unit',
  label: new TranslatableMarkup('Unit'),
  label_collection: new TranslatableMarkup('Units'),
  label_singular: new TranslatableMarkup('unit'),
  label_plural: new TranslatableMarkup('units'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'label',
  ],
  handlers: [
    'list_builder' => UnitListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'default' => ContentEntityForm::class,
      'add' => ContentEntityForm::class,
      'edit' => ContentEntityForm::class,
      'delete' => ContentEntityDeleteForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/units',
    'add-form' => '/admin/content/units/add',
    'edit-form' => '/admin/content/units/{pimlico_unit}/edit',
    'delete-form' => '/admin/content/units/{pimlico_unit}/delete',
  ],
  admin_permission: 'administer pimlico units',
  base_table: 'pimlico_unit',
  label_count: [
    'singular' => '@count unit',
    'plural' => '@count units',
  ],
)]
final class Unit extends ContentEntityBase implements UnitInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
      ->setDescription(new TranslatableMarkup('The shopfront, e.g. "95–97 Pimlico Road" or "Unit 5, Newson’s Yard".'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(new TranslatableMarkup('Address'))
      ->setSetting('available_countries', ['GB' => 'GB'])
      ->setSetting('field_overrides', [
        'givenName' => ['override' => 'hidden'],
        'additionalName' => ['override' => 'hidden'],
        'familyName' => ['override' => 'hidden'],
        'organization' => ['override' => 'hidden'],
      ])
      ->setDisplayOptions('form', ['type' => 'address_default', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['map_buildings'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Map building'))
      ->setDescription(new TranslatableMarkup('Click the shopfront on the map. Add another item if the Unit spans more than one building.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['type' => 'pimlico_map_building_picker', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['area'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Area'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['area' => 'area']])
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['entrance_note'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entrance note'))
      ->setDescription(new TranslatableMarkup('Optional, e.g. "Courtyard entrance".'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapBuildings(): array {
    return array_values(array_filter(array_column($this->get('map_buildings')->getValue(), 'value')));
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine(): string {
    $address = $this->get('address')->first();
    if (!$address) {
      return '';
    }
    $parts = [
      $address->getAddressLine1(),
      $address->getAddressLine2(),
      $address->getLocality(),
      $address->getPostalCode(),
    ];
    return implode(', ', array_filter($parts));
  }

}
