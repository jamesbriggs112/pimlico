<?php

declare(strict_types=1);

namespace Drupal\pimlico_brands;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Brand pages: Home / Brands or Dine / the category's filter / the Brand.
 *
 * Every Brand's path is under /brands, so the path-based breadcrumb would
 * file restaurants under Brands; their section is Dine.
 */
final class BrandBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly BrandGroups $groups,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match, ?CacheableMetadata $cacheable_metadata = NULL): bool {
    $cacheable_metadata?->addCacheContexts(['route']);
    $node = $route_match->getParameter('node');
    return $route_match->getRouteName() === 'entity.node.canonical'
      && $node instanceof NodeInterface
      && $node->bundle() === 'brand';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    $brand = $route_match->getParameter('node');
    $term = $brand->get('field_brand_category')->entity;
    $breadcrumb = (new Breadcrumb())
      ->addCacheContexts(['route'])
      ->addCacheableDependency($brand);

    $links = [Link::createFromRoute($this->t('Home'), '<front>')];
    if ($this->groups->isDine($term)) {
      $links[] = Link::createFromRoute($this->t('Dine'), 'view.brands.page_dine');
      $links[] = Link::createFromRoute($term->label(), 'view.brands.page_dine', [], ['query' => ['category' => $term->id()]]);
    }
    else {
      $links[] = Link::createFromRoute($this->t('Brands'), 'view.brands.page_brands');
      if ($filter = $this->groups->filterFor($term)) {
        $links[] = Link::createFromRoute($filter->label(), 'view.brands.page_brands', [], ['query' => ['category' => $filter->id()]]);
      }
    }
    if ($term) {
      $breadcrumb->addCacheableDependency($this->groups->topCategory($term));
      $breadcrumb->addCacheableDependency($term);
    }
    $links[] = Link::createFromRoute($brand->label(), '<none>');
    return $breadcrumb->setLinks($links);
  }

}
