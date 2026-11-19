import { beforeAll, beforeEach, describe, expect, it } from 'vitest';
import { loadBehaviour } from './drupal-harness.js';

let behaviour;

const header = () => document.querySelector('.header');

describe('header mobile menu', () => {
  beforeAll(async () => {
    window.matchMedia = () => ({ matches: false, addEventListener() {} });
    behaviour = await loadBehaviour(
      '../../components/header/header.js',
      'header'
    );
  });

  beforeEach(() => {
    document.body.innerHTML = `
      <header class="header">
        <button class="header__menu-toggle" aria-expanded="false">Menu</button>
        <a class="header__newsletter-link" href="#newsletter">Sign up to the newsletter</a>
      </header>
      <main></main>`;
    behaviour.attach(document);
  });

  it('opens from the toggle and hides the page behind it', () => {
    document.querySelector('.header__menu-toggle').click();
    expect(header().classList.contains('header--nav-open')).toBe(true);
    expect(
      document
        .querySelector('.header__menu-toggle')
        .getAttribute('aria-expanded')
    ).toBe('true');
    expect(document.querySelector('main').inert).toBe(true);
  });

  it('closes when the newsletter link is followed', () => {
    document.querySelector('.header__menu-toggle').click();
    document.querySelector('.header__newsletter-link').click();
    expect(header().classList.contains('header--nav-open')).toBe(false);
    expect(document.querySelector('main').inert).toBe(false);
  });
});
