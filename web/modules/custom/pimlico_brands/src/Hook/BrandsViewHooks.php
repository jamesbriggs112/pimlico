<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\pimlico_brands\BrandGroups;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Lets /brands?category=interiors filter by a whole category group.
 */
final class BrandsViewHooks {

  public function __construct(
    private readonly BrandGroups $groups,
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * Implements hook_views_pre_view().
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view, string $display_id, array &$args): void {
    if ($view->id() !== 'brands' || $display_id !== 'page_brands') {
      return;
    }
    $request = $this->requestStack->getCurrentRequest();
    $group = (string) $request?->query->get('category', '');
    if (!isset($this->groups->labels()[$group])) {
      return;
    }
    $input = $view->getExposedInput();
    $tids = $this->groups->termIds($group);
    $input['category'] = $tids ? array_combine($tids, $tids) : ['0' => '0'];
    $view->setExposedInput($input);
  }

}
