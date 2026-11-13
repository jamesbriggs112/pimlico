<?php

declare(strict_types=1);

namespace Drupal\Tests\pimlico\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the markup of Pimlico Road components the site relies on.
 */
#[Group('pimlico')]
#[RunTestsInSeparateProcesses]
final class ComponentsThemeTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system', 'user', 'node', 'field', 'text', 'filter', 'options',
    'taxonomy', 'address', 'path_alias', 'views', 'pimlico_map', 'pimlico_brands',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['pimlico'];

  /**
   * The card swipe track's arrows control the track and start hidden.
   */
  public function testCardGridArrows(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'pimlico:card_grid',
      '#props' => ['heading' => 'Similar brands'],
      '#slots' => ['items' => ['#markup' => '<a>One</a><a>Two</a>']],
    ]);

    $track = $crawler->filter('.card-grid__items');
    $this->assertSame('cards-similar-brands-track', $track->attr('id'));
    $this->assertSame('', $crawler->filter('.card-grid__controls')->attr('hidden'));
    foreach (['[data-cards-prev]', '[data-cards-next]'] as $button) {
      $this->assertSame($track->attr('id'), $crawler->filter($button)->attr('aria-controls'));
    }
  }

  /**
   * The active filter is plain text marked current, not a link to itself.
   */
  public function testFilterBarActiveItem(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'pimlico:filter_bar',
      '#props' => [
        'label' => 'Filter brands by category',
        'items' => [
          ['text' => 'All', 'url' => '/brands', 'active' => TRUE],
          ['text' => 'Interiors', 'url' => '/brands?category=interiors'],
        ],
      ],
    ]);

    $current = $crawler->filter('[aria-current="true"]');
    $this->assertSame('All', trim($current->text()));
    $this->assertSame('span', $current->nodeName());
    $this->assertCount(1, $crawler->filter('a.filter-bar__link'));
  }

  /**
   * Buttons are Dark unless a variant is chosen.
   *
   * Rendered through a Twig include, as templates use it: a #type component
   * render array would pass its own (empty) #variant as the variant prop.
   */
  public function testButtonVariants(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'inline_template',
      '#template' => "{{ include('pimlico:button', { text: 'Plan your visit', url: '/visit' }, with_context = false) }}"
      . "{{ include('pimlico:button', { text: 'Subscribe', variant: 'yellow' }, with_context = false) }}",
    ]);

    $this->assertCount(1, $crawler->filter('a.btn.btn--dark[href="/visit"]'));
    $this->assertCount(1, $crawler->filter('button.btn.btn--yellow'));
  }

}
