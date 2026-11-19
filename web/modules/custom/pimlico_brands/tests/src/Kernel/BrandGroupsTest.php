<?php

declare(strict_types=1);

namespace Drupal\Tests\pimlico_brands\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\pimlico_brands\BrandGroups;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Brands page filters and the Dine categories.
 */
#[Group('pimlico_brands')]
final class BrandGroupsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'text', 'filter', 'taxonomy', 'node', 'views', 'pimlico_brands'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    Vocabulary::create(['vid' => 'brand_category', 'name' => 'Brand category'])->save();
  }

  /**
   * Every top-level category but Food & Drink is a filter, in weight order.
   */
  public function testFilters(): void {
    $interiors = $this->term('Interiors', 0);
    $antiques = $this->term('Antiques & art', 1);
    $food = $this->term('Food & Drink', 2, uuid: BrandGroups::FOOD_AND_DRINK);
    $cafes = $this->term('Cafés', 0, parent: $food);
    // A category added by an editor, weighted first, with a sub-category.
    $lighting = $this->term('Lighting', -1);
    $lamps = $this->term('Lamps', 0, parent: $lighting);

    $groups = $this->container->get(BrandGroups::class);
    $this->assertSame([
      (int) $lighting->id() => 'Lighting',
      (int) $interiors->id() => 'Interiors',
      (int) $antiques->id() => 'Antiques & art',
    ], $groups->labels());

    $this->assertSame([(int) $interiors->id()], $groups->termIds((string) $interiors->id()));
    $this->assertSame([(int) $lighting->id(), (int) $lamps->id()], $groups->termIds((int) $lighting->id()));
    $this->assertSame([], $groups->termIds((string) $food->id()), 'Food & Drink is not a Brands filter.');
    $this->assertSame([], $groups->termIds((string) $cafes->id()), 'A sub-category is not a Brands filter.');
    $this->assertSame([], $groups->termIds('interiors'), 'Filters are keyed by term ID.');
    $this->assertSame([
      (int) $lighting->id(),
      (int) $lamps->id(),
      (int) $interiors->id(),
      (int) $antiques->id(),
    ], $groups->allTermIds());

    $this->assertSame('Lamps', $groups->labelFor($lamps), 'A Brand is labelled with its own sub-category.');
    $this->assertSame('Interiors', $groups->labelFor($interiors), 'A Brand with no sub-category is labelled with the top-level category.');
    $this->assertSame((int) $lighting->id(), (int) $groups->filterFor($lamps)->id());
    $this->assertNull($groups->filterFor($cafes));
    $this->assertSame('Cafés', $groups->labelFor($cafes), 'Dine Brands are labelled with their own category, not Food & Drink.');
    $this->assertSame('', $groups->labelFor(NULL));

    $this->assertSame([(int) $food->id(), (int) $cafes->id()], $groups->dineTermIds());
    $this->assertTrue($groups->isDine($cafes));
    $this->assertFalse($groups->isDine($interiors));
  }

  /**
   * The street map has the Brands filters, then Dine's, never Food & Drink.
   */
  public function testMapCategories(): void {
    $interiors = $this->term('Interiors', 0);
    $antiques = $this->term('Antiques & art', 1);
    $food = $this->term('Food & Drink', 2, uuid: BrandGroups::FOOD_AND_DRINK);
    $pubs = $this->term('Pubs and restaurants', 0, parent: $food);
    $cafes = $this->term('Cafés', 1, parent: $food);
    // Tags added by editors: a Dine tag weighted first, a Brands category
    // weighted last with a sub-category, and a tag below a Dine tag.
    $bakeries = $this->term('Bakeries', -1, parent: $food);
    $lighting = $this->term('Lighting', 5);
    $lamps = $this->term('Lamps', 0, parent: $lighting);
    $espresso = $this->term('Espresso bars', 0, parent: $cafes);

    $groups = $this->container->get(BrandGroups::class);
    $labels = array_map(static fn(TermInterface $term) => (string) $term->label(), $groups->mapCategories());
    $this->assertSame([
      (int) $interiors->id() => 'Interiors',
      (int) $antiques->id() => 'Antiques & art',
      (int) $lighting->id() => 'Lighting',
      (int) $bakeries->id() => 'Bakeries',
      (int) $pubs->id() => 'Pubs and restaurants',
      (int) $cafes->id() => 'Cafés',
    ], $labels);
    $this->assertSame(array_keys($groups->labels()), array_slice(array_keys($labels), 0, 3), 'The Brands filters come first, as on Brands.');
    $this->assertSame([(int) $bakeries->id(), (int) $pubs->id(), (int) $cafes->id()], array_keys($groups->dineFilters()));

    $category = static fn(?TermInterface $term) => $groups->mapCategoryFor($term)?->label();
    $this->assertSame('Interiors', $category($interiors));
    $this->assertSame('Lighting', $category($lamps), 'A Brands sub-category belongs to its top-level category.');
    $this->assertSame('Cafés', $category($cafes), 'A Dine tag is its own map category.');
    $this->assertSame('Cafés', $category($espresso), 'A tag below a Dine tag belongs to that Dine tag.');
    $this->assertSame('Bakeries', $category($bakeries));
    $this->assertNull($category($food), 'Food & Drink is never a map category.');
    $this->assertNull($category(NULL));

    // Renaming Food & Drink changes nothing: it is found by UUID.
    $food->setName('Eat & drink')->save();
    $this->assertSame('Pubs and restaurants', $category($pubs));
    $this->assertCount(6, $groups->mapCategories());
  }

  /**
   * Renaming categories keeps their filters and Food & Drink's place on Dine.
   */
  public function testRenamedCategories(): void {
    $interiors = $this->term('Interiors', 0);
    $food = $this->term('Food & Drink', 1, uuid: BrandGroups::FOOD_AND_DRINK);
    $interiors->setName('Home')->save();
    $food->setName('Eat & drink')->save();

    $groups = $this->container->get(BrandGroups::class);
    $this->assertSame([(int) $interiors->id() => 'Home'], $groups->labels());
    $this->assertSame('Home', $groups->labelFor($interiors));
    $this->assertSame('Eat & drink', $groups->labelFor($food));
    $this->assertTrue($groups->isDine($food));
    $this->assertSame([(int) $food->id()], $groups->dineTermIds());
  }

  /**
   * Creates a Brand Category.
   */
  private function term(string $name, int $weight, ?TermInterface $parent = NULL, ?string $uuid = NULL): TermInterface {
    $values = ['vid' => 'brand_category', 'name' => $name, 'weight' => $weight];
    if ($parent) {
      $values['parent'] = $parent->id();
    }
    if ($uuid) {
      $values['uuid'] = $uuid;
    }
    $term = Term::create($values);
    $term->save();
    return $term;
  }

}
