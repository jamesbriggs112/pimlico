import { beforeAll, beforeEach, describe, expect, it } from 'vitest';
import { loadBehaviour } from './drupal-harness.js';

let behaviour;

const map = () => document.getElementById('map');
const buttons = () => [...document.querySelectorAll('button')];

describe('street map filter', () => {
  beforeAll(async () => {
    behaviour = await loadBehaviour(
      '../../components/street_map/street_map.js',
      'streetMapFilter'
    );
  });

  beforeEach(() => {
    document.body.innerHTML = `
      <div data-map-filter="map">
        <button data-category="" aria-pressed="true">All</button>
        <button data-category="Interiors" aria-pressed="false">Interiors</button>
      </div>
      <pimlico-map id="map"></pimlico-map>`;
    behaviour.attach(document);
  });

  it('sets the map category and marks the pressed button', () => {
    buttons()[1].click();

    expect(map().getAttribute('category')).toBe('Interiors');
    expect(buttons().map((b) => b.getAttribute('aria-pressed'))).toEqual([
      'false',
      'true',
    ]);
  });

  it('clears the category for All', () => {
    buttons()[1].click();
    buttons()[0].click();

    expect(map().hasAttribute('category')).toBe(false);
    expect(buttons()[0].getAttribute('aria-pressed')).toBe('true');
  });
});
