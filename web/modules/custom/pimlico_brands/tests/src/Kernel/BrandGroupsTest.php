<?php

declare(strict_types=1);

namespace Drupal\Tests\pimlico_brands\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\pimlico_brands\BrandGroups;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Brands page category groups.
 */
#[Group('pimlico_brands')]
final class BrandGroupsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'text', 'filter', 'taxonomy', 'node', 'views', 'pimlico_brands'];

  /**
   * Interiors gathers three categories; Food & Drink is in no group.
   */
  public function testGroups(): void {
    $this->installEntitySchema('taxonomy_term');
    Vocabulary::create(['vid' => 'brand_category', 'name' => 'Brand category'])->save();
    $terms = [];
    foreach (['Home & Interiors', 'Soft furnishings and fabrics', 'Lighting', 'Antiques and art', 'Food & Drink'] as $name) {
      $terms[$name] = Term::create(['vid' => 'brand_category', 'name' => $name]);
      $terms[$name]->save();
    }
    $cafes = Term::create(['vid' => 'brand_category', 'name' => 'Cafés', 'parent' => $terms['Food & Drink']->id()]);
    $cafes->save();

    $groups = $this->container->get(BrandGroups::class);
    $this->assertSame(['interiors', 'antiques-and-art'], array_keys($groups->labels()));

    $interiors = $groups->termIds('interiors');
    sort($interiors);
    $expected = [
      (int) $terms['Home & Interiors']->id(),
      (int) $terms['Soft furnishings and fabrics']->id(),
      (int) $terms['Lighting']->id(),
    ];
    sort($expected);
    $this->assertSame($expected, $interiors);
    $this->assertSame([(int) $terms['Antiques and art']->id()], $groups->termIds('antiques-and-art'));
    $this->assertSame([], $groups->termIds('food'));

    $this->assertSame('Interiors', $groups->labelFor($terms['Lighting']));
    $this->assertSame('Antiques & art', $groups->labelFor($terms['Antiques and art']));
    $this->assertNull($groups->groupFor($cafes));
    $this->assertSame('Cafés', $groups->labelFor($cafes));
  }

}
