<?php

declare(strict_types=1);

namespace Drupal\pimlico_sections;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * The auto sections: sections that fill themselves from the page they're on.
 *
 * Similar brands (on a Brand) and Related (on an Article or Press article)
 * have no required fields. Their content comes from the host node: its picks
 * (Similar brands, Related stories or brands) or, when there are none, the
 * same fallback listing the page template used to print. Every Brand and
 * Story starts with one at the end of its sections; editors may move or
 * remove it.
 *
 * The one list of which section fills itself on which content type, used by
 * the presave default, the deploy hook that added them to existing pages,
 * the seed scripts and the theme (which prints a closing auto section after
 * the page's fixed parts).
 */
final class AutoSections {

  /**
   * The auto section's Paragraph type, keyed by host node bundle.
   */
  public const TYPES = [
    'brand' => 'similar_brands',
    'article' => 'related',
    'press_article' => 'related',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Whether a Paragraph type is an auto section.
   */
  public function isAutoSection(ParagraphInterface $paragraph): bool {
    return in_array($paragraph->bundle(), self::TYPES, TRUE);
  }

  /**
   * Whether the node has its content type's auto section anywhere.
   *
   * Content types with no auto section count as having it, so callers never
   * add one to them.
   */
  public function hasAutoSection(NodeInterface $node): bool {
    $type = self::TYPES[$node->bundle()] ?? NULL;
    if ($type === NULL || !$node->hasField('field_sections')) {
      return TRUE;
    }
    foreach ($node->get('field_sections')->referencedEntities() as $section) {
      if ($section->bundle() === $type) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Creates the content type's auto section, unsaved, or NULL if it has none.
   */
  public function create(string $bundle): ?ParagraphInterface {
    $type = self::TYPES[$bundle] ?? NULL;
    if ($type === NULL) {
      return NULL;
    }
    /** @var \Drupal\paragraphs\ParagraphInterface */
    return $this->entityTypeManager->getStorage('paragraph')->create(['type' => $type]);
  }

  /**
   * Appends the auto section to the node's sections when it has none.
   *
   * The node isn't saved. The section is: hook_node_presave() runs after the
   * sections field's own preSave(), which is what saves new sections, so an
   * unsaved one added there would be lost. The field's postSave() then sets
   * the section's parent.
   *
   * @return bool
   *   TRUE if a section was added.
   */
  public function append(NodeInterface $node): bool {
    if ($this->hasAutoSection($node)) {
      return FALSE;
    }
    $section = $this->create($node->bundle());
    $section->save();
    $node->get('field_sections')->appendItem($section);
    return TRUE;
  }

}
