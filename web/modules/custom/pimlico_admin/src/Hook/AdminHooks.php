<?php

declare(strict_types=1);

namespace Drupal\pimlico_admin\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Adds the Pimlico Road admin styles wherever Gin renders a page.
 */
final class AdminHooks {

  public function __construct(
    private readonly ThemeManagerInterface $themeManager,
  ) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    if ($this->themeManager->getActiveTheme()->getName() === 'gin') {
      $attachments['#attached']['library'][] = 'pimlico_admin/admin';
    }
  }

}
