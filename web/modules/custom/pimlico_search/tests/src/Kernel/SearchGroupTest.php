<?php

declare(strict_types=1);

namespace Drupal\Tests\pimlico_search\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\pimlico_search\Plugin\search_api\processor\SearchGroup;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests which search group each kind of content lands in.
 *
 * @group pimlico_search
 */
final class SearchGroupTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system', 'path_alias', 'user', 'node', 'field', 'text', 'filter', 'taxonomy',
    'search_api', 'pimlico_search',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    foreach (['brand', 'article', 'podcast_episode', 'press_article', 'page'] as $type) {
      NodeType::create(['type' => $type, 'name' => $type])->save();
    }
    Vocabulary::create(['vid' => 'brand_category', 'name' => 'Brand category'])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_brand_category',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
    ])->save();
    FieldConfig::create(['field_name' => 'field_brand_category', 'entity_type' => 'node', 'bundle' => 'brand'])->save();
  }

  /**
   * Brands split into brands and dine; stories into news and press.
   */
  public function testGroups(): void {
    $food = Term::create(['vid' => 'brand_category', 'name' => SearchGroup::DINE_CATEGORY]);
    $food->save();
    $cafes = Term::create(['vid' => 'brand_category', 'name' => 'Cafés', 'parent' => $food->id()]);
    $cafes->save();
    $lighting = Term::create(['vid' => 'brand_category', 'name' => 'Lighting']);
    $lighting->save();

    $cases = [
      'brands' => Node::create(['type' => 'brand', 'title' => 'A', 'field_brand_category' => $lighting]),
      'dine' => Node::create(['type' => 'brand', 'title' => 'B', 'field_brand_category' => $food]),
      'news' => Node::create(['type' => 'podcast_episode', 'title' => 'D']),
      'press' => Node::create(['type' => 'press_article', 'title' => 'E']),
    ];
    foreach ($cases as $expected => $node) {
      $this->assertSame($expected, SearchGroup::groupFor($node), $node->label());
    }
    $child = Node::create(['type' => 'brand', 'title' => 'C', 'field_brand_category' => $cafes]);
    $this->assertSame('dine', SearchGroup::groupFor($child));
    $this->assertSame('news', SearchGroup::groupFor(Node::create(['type' => 'article', 'title' => 'F'])));
    $this->assertSame('brands', SearchGroup::groupFor(Node::create(['type' => 'brand', 'title' => 'G'])));
    $this->assertNull(SearchGroup::groupFor(Node::create(['type' => 'page', 'title' => 'H'])));
  }

}
