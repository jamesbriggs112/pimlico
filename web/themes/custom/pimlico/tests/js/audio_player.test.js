import { beforeAll, beforeEach, describe, expect, it } from 'vitest';
import { loadBehaviour } from './drupal-harness.js';

let behaviour;

const audio = () => document.querySelector('audio');
const toggle = () => document.querySelector('[data-audio-toggle]');

describe('audio player', () => {
  beforeAll(async () => {
    behaviour = await loadBehaviour(
      '../../components/audio_player/audio_player.js',
      'audioPlayer'
    );
  });

  beforeEach(() => {
    document.body.innerHTML = `
      <div data-component-id="pimlico:audio_player">
        <audio src="episode.mp3" controls data-audio></audio>
        <button aria-pressed="false" data-audio-toggle hidden>
          <span data-audio-label>Play episode: Fifty years on the road</span>
        </button>
        <p data-audio-meta>Episode 1 · 42 min</p>
        <div data-audio-progress hidden>
          <input type="range" data-audio-seek>
        </div>
      </div>`;
    behaviour.attach(document);
  });

  it('replaces the native controls with the player', () => {
    expect(audio().controls).toBe(false);
    expect(toggle().hidden).toBe(false);
    expect(document.querySelector('[data-audio-progress]').hidden).toBe(false);
  });

  it('labels the button Pause while playing', () => {
    Object.defineProperty(audio(), 'paused', {
      value: false,
      configurable: true,
    });
    audio().dispatchEvent(new Event('play'));

    expect(toggle().getAttribute('aria-pressed')).toBe('true');
    expect(document.querySelector('[data-audio-label]').textContent).toBe(
      'Pause episode: Fifty years on the road'
    );
  });
});
