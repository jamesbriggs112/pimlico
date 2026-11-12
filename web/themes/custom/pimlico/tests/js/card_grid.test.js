import { beforeAll, describe, expect, it, vi } from 'vitest';
import { loadBehaviour } from './drupal-harness.js';

let behaviour;

// jsdom has no layout, so the track's sizes are set by hand.
const size = (element, scrollWidth, clientWidth) => {
  Object.defineProperty(element, 'scrollWidth', {
    value: scrollWidth,
    configurable: true,
  });
  Object.defineProperty(element, 'clientWidth', {
    value: clientWidth,
    configurable: true,
  });
};

const render = () => {
  document.body.innerHTML = `
    <section class="card-grid">
      <div class="card-grid__items"><a>One</a><a>Two</a><a>Three</a></div>
      <div class="card-grid__controls" hidden>
        <button data-cards-prev>Previous</button>
        <button data-cards-next>Next</button>
      </div>
    </section>`;
  return document.querySelector('.card-grid__items');
};

describe('card grid arrows', () => {
  beforeAll(async () => {
    window.matchMedia = () => ({ matches: false });
    behaviour = await loadBehaviour(
      '../../components/card_grid/card_grid.js',
      'cardGrid'
    );
  });

  it('stay hidden when every card fits', () => {
    size(render(), 900, 900);
    behaviour.attach(document);

    expect(document.querySelector('.card-grid__controls').hidden).toBe(true);
  });

  it('show when cards overflow, with Previous disabled at the start', () => {
    size(render(), 960, 390);
    behaviour.attach(document);

    expect(document.querySelector('.card-grid__controls').hidden).toBe(false);
    expect(document.querySelector('[data-cards-prev]').disabled).toBe(true);
    expect(document.querySelector('[data-cards-next]').disabled).toBe(false);
  });

  it('scroll the track when Next is pressed', () => {
    const track = render();
    size(track, 960, 390);
    track.scrollTo = vi.fn();
    behaviour.attach(document);

    document.querySelector('[data-cards-next]').click();

    expect(track.scrollTo).toHaveBeenCalledOnce();
  });
});
