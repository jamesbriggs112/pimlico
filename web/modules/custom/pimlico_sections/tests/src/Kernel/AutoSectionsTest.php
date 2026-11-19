<?php

declare(strict_types=1);

namespace Drupal\Tests\pimlico_sections\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\pimlico_sections\AutoSections;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that Brands and Stories end with their auto section.
 */
#[Group('pimlico_sections')]
final class AutoSectionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'text',
    'filter',
    'node',
    'entity_reference_revisions',
    'paragraphs',
    'pimlico_sections',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node', 'filter']);

    foreach (['similar_brands', 'related', 'text_wysiwyg_'] as $type) {
      ParagraphsType::create(['id' => $type, 'label' => $type])->save();
    }
    FieldStorageConfig::create([
      'field_name' => 'field_sections',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => -1,
      'settings' => ['target_type' => 'paragraph'],
    ])->save();
    foreach (['brand', 'article', 'press_article', 'page'] as $bundle) {
      NodeType::create(['type' => $bundle, 'name' => $bundle])->save();
      FieldConfig::create([
        'field_name' => 'field_sections',
        'entity_type' => 'node',
        'bundle' => $bundle,
      ])->save();
    }
  }

  /**
   * A new Brand or Story gets its auto section after the sections it has.
   */
  public function testNewNodesEndWithTheirAutoSection(): void {
    $brand = $this->createNode('brand', [$this->text()]);
    $this->assertSame(['text_wysiwyg_', 'similar_brands'], $this->sectionTypes($brand));
    $sections = $brand->get('field_sections')->referencedEntities();
    $this->assertSame($brand->id(), end($sections)->getParentEntity()->id(), 'The auto section is saved with the Brand as its parent.');

    $this->assertSame(['related'], $this->sectionTypes($this->createNode('article')));
    $this->assertSame(['related'], $this->sectionTypes($this->createNode('press_article')));
    $this->assertSame([], $this->sectionTypes($this->createNode('page')), 'Pages have no auto section.');
  }

  /**
   * One the editor placed, or removed later, is left alone.
   */
  public function testEditorsChoiceIsKept(): void {
    $related = Paragraph::create(['type' => 'related']);
    $article = $this->createNode('article', [$related, $this->text()]);
    $this->assertSame(['related', 'text_wysiwyg_'], $this->sectionTypes($article), 'An auto section the editor moved up is not added again.');

    $article->set('field_sections', [$this->text()]);
    $article->save();
    $this->assertSame(['text_wysiwyg_'], $this->sectionTypes($this->reload($article)), 'Removing it from an existing Story sticks.');
  }

  /**
   * Appending to existing nodes (the deploy hook) is idempotent.
   */
  public function testAppendIsIdempotent(): void {
    $brand = $this->createNode('brand');
    $brand->set('field_sections', [$this->text()]);
    $brand->save();

    $auto = $this->container->get(AutoSections::class);
    $this->assertTrue($auto->append($brand));
    $brand->save();
    $this->assertFalse($auto->append($this->reload($brand)));
    $this->assertSame(['text_wysiwyg_', 'similar_brands'], $this->sectionTypes($this->reload($brand)));
    $this->assertFalse($auto->append($this->createNode('page')), 'Content types without an auto section get none.');
  }

  /**
   * Creates and saves a node with these sections.
   */
  private function createNode(string $bundle, array $sections = []): NodeInterface {
    $node = Node::create(['type' => $bundle, 'title' => $bundle, 'field_sections' => $sections]);
    $node->save();
    return $this->reload($node);
  }

  /**
   * Creates an unsaved Text section.
   */
  private function text(): Paragraph {
    return Paragraph::create(['type' => 'text_wysiwyg_']);
  }

  /**
   * Reloads a node from storage.
   */
  private function reload(NodeInterface $node): NodeInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache([$node->id()]);
    return $storage->load($node->id());
  }

  /**
   * The node's section types, in order.
   */
  private function sectionTypes(NodeInterface $node): array {
    return array_map(static fn($section) => $section->bundle(), $node->get('field_sections')->referencedEntities());
  }

}
