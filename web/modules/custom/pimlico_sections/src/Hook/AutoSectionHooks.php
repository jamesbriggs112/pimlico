<?php

declare(strict_types=1);

namespace Drupal\pimlico_sections\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\pimlico_sections\AutoSections;

/**
 * New Brands and Stories end with their auto section.
 *
 * Added on first save rather than in the add form, so sections an editor
 * adds on the form stay above it, and Brands from the import and the seed
 * scripts get it too. Editors can move or remove it afterwards.
 */
final class AutoSectionHooks {

  public function __construct(
    private readonly AutoSections $autoSections,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_presave() for node.
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node): void {
    if ($node->isNew()) {
      $this->autoSections->append($node);
    }
  }

}
