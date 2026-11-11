<?php

declare(strict_types=1);

namespace Drupal\Tests\pimlico_map\Kernel;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\pimlico_map\BrandFeed;
use Drupal\pimlico_map\Controller\BrandFeedController;
use Drupal\pimlico_map\Entity\Unit;
use Drupal\pimlico_map\MapBuilder;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Tenancy Feed's shape, filtering and cacheability.
 */
#[Group('pimlico_map')]
final class BrandFeedTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system', 'user', 'node', 'field', 'text', 'filter', 'options',
    'taxonomy', 'address', 'path_alias', 'pimlico_map',
  ];

  /**
   * The Food & Drink category, with Cafés below it.
   */
  private Term $food;

  /**
   * The Cafés sub-category.
   */
  private Term $cafes;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('pimlico_unit');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter', 'node']);
    $this->setUpCurrentUser([], ['access content']);

    NodeType::create(['type' => 'brand', 'name' => 'Brand'])->save();
    Vocabulary::create(['vid' => 'brand_category', 'name' => 'Brand category'])->save();
    Vocabulary::create(['vid' => 'area', 'name' => 'Area'])->save();
    $fields = [
      'field_brand_category' => ['entity_reference', ['target_type' => 'taxonomy_term'], 1],
      'field_units' => ['entity_reference', ['target_type' => 'pimlico_unit'], -1],
      'field_tenancy_status' => [
        'list_string',
        ['allowed_values' => ['active' => 'Active', 'coming_soon' => 'Coming soon', 'closed' => 'Closed']],
        1,
      ],
      'field_display_on_map' => ['boolean', [], 1],
      'field_map_label' => ['string', [], 1],
      'field_address' => ['address', [], 1],
    ];
    foreach ($fields as $name => [$type, $settings, $cardinality]) {
      FieldStorageConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => $type,
        'settings' => $settings,
        'cardinality' => $cardinality,
      ])->save();
      FieldConfig::create(['field_name' => $name, 'entity_type' => 'node', 'bundle' => 'brand'])->save();
    }

    $this->food = Term::create(['vid' => 'brand_category', 'name' => 'Food & Drink']);
    $this->food->save();
    $this->cafes = Term::create(['vid' => 'brand_category', 'name' => 'Cafés', 'parent' => $this->food->id()]);
    $this->cafes->save();
  }

  /**
   * Only published, active Brands shown on the map with a placed Unit appear.
   */
  public function testFeed(): void {
    $unit = Unit::create([
      'label' => '95–97 Pimlico Road',
      'map_buildings' => ['w535769837', 'w535769838'],
      'address' => [
        'country_code' => 'GB',
        'address_line1' => '95–97 Pimlico Road',
        'locality' => 'London',
        'postal_code' => 'SW1W 8PH',
      ],
    ]);
    $unit->save();
    $unplaced = Unit::create(['label' => 'Unit 9, Newson’s Yard']);
    $unplaced->save();

    $jamb = $this->createBrand('Jamb', 'jamb', [$unit], ['field_map_label' => 'Jamb Ltd']);
    $this->createBrand('Café', 'cafe', [$unit], ['field_brand_category' => $this->cafes]);
    $this->createBrand('Closed', 'closed', [$unit], ['field_tenancy_status' => 'closed']);
    $this->createBrand('Hidden', 'hidden', [$unit], ['field_display_on_map' => 0]);
    $this->createBrand('Draft', 'draft', [$unit], ['status' => NodeInterface::NOT_PUBLISHED]);
    $this->createBrand('Unplaced', 'unplaced', [$unplaced]);

    $response = $this->container->get('class_resolver')->getInstanceFromDefinition(BrandFeedController::class)();
    $this->assertInstanceOf(CacheableJsonResponse::class, $response);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(['Café', 'Jamb'], array_column($data['brands'], 'name'));
    [$cafe, $item] = $data['brands'];
    $this->assertSame([
      'id' => 'jamb',
      'name' => 'Jamb',
      'label' => 'Jamb Ltd',
      'category' => 'Food & Drink',
      'url' => '/brands/jamb',
      'address' => '95–97 Pimlico Road, London, SW1W 8PH',
      'buildings' => ['w535769837', 'w535769838'],
      'status' => 'active',
    ], $item);
    // Sub-categories roll up to the top-level term the map filters by.
    $this->assertSame('Food & Drink', $cafe['category']);
    $this->assertArrayNotHasKey('label', $cafe);

    $tags = $response->getCacheableMetadata()->getCacheTags();
    $expected = [
      'node_list:brand',
      'pimlico_unit_list',
      'taxonomy_term_list:brand_category',
      'node:' . $jamb->id(),
      'pimlico_unit:' . $unit->id(),
    ];
    foreach ($expected as $tag) {
      $this->assertContains($tag, $tags);
    }
  }

  /**
   * Brand pages get the map in brand mode only when the Brand is on it.
   */
  public function testBrandMap(): void {
    $unit = Unit::create(['label' => '74 Pimlico Road', 'map_buildings' => ['w535769896']]);
    $unit->save();
    $on = $this->createBrand('Anthony Outred', 'anthony-outred', [$unit]);
    $off = $this->createBrand('Coming', 'coming', [$unit], ['field_tenancy_status' => 'coming_soon']);

    $builder = $this->container->get(MapBuilder::class);
    $build = $builder->brand($on);
    $this->assertSame('pimlico_map', $build['#theme']);
    $this->assertSame('brand', $build['#mode']);
    $this->assertSame('anthony-outred', $build['#brand']);
    $this->assertStringContainsString('assets/pimlico-map.json?v=', $build['#src']);
    $this->assertContains('pimlico_unit:' . $unit->id(), $build['#cache']['tags']);

    $this->assertArrayNotHasKey('#theme', $builder->brand($off));
    $this->assertFalse($this->container->get(BrandFeed::class)->isOnMap($off));
  }

  /**
   * Creates a published, active Brand with a path alias.
   */
  private function createBrand(string $title, string $slug, array $units, array $values = []): NodeInterface {
    $node = Node::create($values + [
      'type' => 'brand',
      'title' => $title,
      'status' => NodeInterface::PUBLISHED,
      'field_brand_category' => $this->food,
      'field_units' => $units,
      'field_tenancy_status' => 'active',
      'field_display_on_map' => 1,
    ]);
    $node->save();
    PathAlias::create(['path' => '/node/' . $node->id(), 'alias' => '/brands/' . $slug])->save();
    return $node;
  }

}
