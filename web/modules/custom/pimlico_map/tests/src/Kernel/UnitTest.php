<?php

declare(strict_types=1);

namespace Drupal\Tests\pimlico_map\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\pimlico_map\Entity\Unit;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Unit access, the Units list and the Pimlico Road address defaults.
 */
#[Group('pimlico_map')]
final class UnitTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system', 'user', 'node', 'field', 'text', 'filter', 'options',
    'taxonomy', 'address', 'path_alias', 'pimlico_map',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('pimlico_unit');
  }

  /**
   * Only users who administer Units may view, change or delete them.
   */
  public function testAccess(): void {
    $unit = Unit::create(['label' => '95–97 Pimlico Road']);
    $unit->save();
    $handler = $this->container->get('entity_type.manager')->getAccessControlHandler('pimlico_unit');

    $editor = $this->createUser(['administer pimlico units']);
    $visitor = $this->createUser(['access content']);

    foreach (['view', 'update', 'delete'] as $operation) {
      $this->assertTrue($unit->access($operation, $editor), "Editor can $operation.");
      $this->assertFalse($unit->access($operation, $visitor), "Visitor cannot $operation.");
    }
    $this->assertTrue($handler->createAccess(NULL, $editor));
    $this->assertFalse($handler->createAccess(NULL, $visitor));
  }

  /**
   * The Units list shows each Unit's name and its map buildings, A to Z.
   */
  public function testListBuilder(): void {
    $this->setUpCurrentUser([], ['administer pimlico units']);
    Unit::create(['label' => 'B Unit', 'map_buildings' => ['w123', 'w124']])->save();
    Unit::create(['label' => 'A Unit'])->save();

    $build = $this->container->get('entity_type.manager')->getListBuilder('pimlico_unit')->render();
    $rows = array_values($build['table']['#rows']);

    $this->assertSame(['A Unit', 'B Unit'], array_column($rows, 'label'));
    $this->assertSame('Not on the map', (string) $rows[0]['buildings']);
    $this->assertSame('w123, w124', $rows[1]['buildings']);
  }

  /**
   * A Unit's address gets London and the United Kingdom on save.
   */
  public function testAddressDefaults(): void {
    $unit = Unit::create([
      'label' => '74 Pimlico Road',
      // As the form sends it: the hidden country, no town.
      'address' => [
        'country_code' => 'GB',
        'address_line1' => '74 Pimlico Road',
        'postal_code' => 'SW1W 8LS',
        'administrative_area' => 'City of London',
      ],
    ]);
    $unit->save();

    $address = $unit->get('address')->first();
    $this->assertSame('London', $address->getLocality());
    $this->assertSame('GB', $address->getCountryCode());
    $this->assertSame('', (string) $address->getAdministrativeArea());
  }

}
