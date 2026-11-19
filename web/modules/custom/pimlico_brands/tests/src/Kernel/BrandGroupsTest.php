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
   * Each filter is one category; Food & Drink is in no group.
   */
  public function testGroups(): void {
    $this->installEntitySchema('taxonomy_term');
    Vocabulary::create(['vid' => 'brand_category', 'name' => 'Brand category'])->save();
    $terms = [];
    $uuids = [
      'Interiors' => BrandGroups::INTERIORS,
      'Antiques & art' => BrandGroups::ANTIQUES_AND_ART,
      'Food & Drink' => BrandGroups::FOOD_AND_DRINK,
    ];
    foreach ($uuids as $name => $uuid) {
      $terms[$name] = Term::create(['vid' => 'brand_category', 'name' => $name, 'uuid' => $uuid]);
      $terms[$name]->save();
    }
    $cafes = Term::create(['vid' => 'brand_category', 'name' => 'Cafés', 'parent' => $terms['Food & Drink']->id()]);
    $cafes->save();

    $groups = $this->container->get(BrandGroups::class);
    $this->assertSame(['interiors', 'antiques-and-art'], array_keys($groups->labels()));
    $this->assertSame([(int) $terms['Interiors']->id()], $groups->termIds('interiors'));
    $this->assertSame([(int) $terms['Antiques & art']->id()], $groups->termIds('antiques-and-art'));
    $this->assertSame([], $groups->termIds('food'));

    $this->assertSame('Interiors', $groups->labelFor($terms['Interiors']));
    $this->assertSame('Antiques & art', $groups->labelFor($terms['Antiques & art']));
    $this->assertNull($groups->groupFor($cafes));
    $this->assertSame('Cafés', $groups->labelFor($cafes));

    $food = (int) $terms['Food & Drink']->id();
    $this->assertSame([$food, (int) $cafes->id()], $groups->dineTermIds());
    $this->assertTrue($groups->isDine($cafes));
    $this->assertFalse($groups->isDine($terms['Interiors']));
  }

  /**
   * Renaming a category keeps its filter and its place on Dine.
   */
  public function testRenamedCategories(): void {
    $this->installEntitySchema('taxonomy_term');
    Vocabulary::create(['vid' => 'brand_category', 'name' => 'Brand category'])->save();
    $interiors = Term::create(['vid' => 'brand_category', 'name' => 'Home', 'uuid' => BrandGroups::INTERIORS]);
    $interiors->save();
    $food = Term::create(['vid' => 'brand_category', 'name' => 'Eat & drink', 'uuid' => BrandGroups::FOOD_AND_DRINK]);
    $food->save();

    $groups = $this->container->get(BrandGroups::class);
    $this->assertSame(['interiors' => 'Home'], $groups->labels());
    $this->assertSame('interiors', $groups->groupFor($interiors));
    $this->assertTrue($groups->isDine($food));
  }

}
