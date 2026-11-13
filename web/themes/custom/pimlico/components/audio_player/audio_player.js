/**
 * @file
 * Audio player: replaces the native controls with the Figma player (play and
 * pause, elapsed time, a seekable progress bar). Without JavaScript the
 * browser's own player shows instead.
 */
(function (Drupal, once) {
  const time = (s) => {
    if (!Number.isFinite(s)) {
      return '00:00';
    }
    const m = Math.floor(s / 60);
    return `${String(m).padStart(2, '0')}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
  };

  Drupal.behaviors.audioPlayer = {
    attach(context) {
      once(
        'audio-player',
        '[data-component-id="pimlico:audio_player"]',
        context
      ).forEach((player) => {
        const audio = player.querySelector('[data-audio]');
        const toggle = player.querySelector('[data-audio-toggle]');
        const seek = player.querySelector('[data-audio-seek]');
        const meta = player.querySelector('[data-audio-meta]');
        const label = player.querySelector('[data-audio-label]');
        const original = meta.textContent;
        const title = label.textContent.split(': ').slice(1).join(': ');

        audio.controls = false;
        toggle.hidden = false;
        seek.closest('[data-audio-progress]').hidden = false;
        player.classList.add('audio-player--enhanced');

        const render = () => {
          const playing = !audio.paused;
          player.classList.toggle('audio-player--playing', playing);
          toggle.setAttribute('aria-pressed', playing ? 'true' : 'false');
          label.textContent = `${playing ? Drupal.t('Pause episode') : Drupal.t('Play episode')}: ${title}`;
          const pct = audio.duration
            ? (audio.currentTime / audio.duration) * 100
            : 0;
          seek.value = pct;
          seek.style.setProperty('--_played', `${pct}%`);
          seek.setAttribute(
            'aria-valuetext',
            `${time(audio.currentTime)} / ${time(audio.duration)}`
          );
          meta.textContent =
            playing || audio.currentTime > 0
              ? `${Drupal.t('Now playing')} · ${time(audio.currentTime)} / ${time(audio.duration)}`
              : original;
        };

        toggle.addEventListener('click', () =>
          audio.paused ? audio.play() : audio.pause()
        );
        seek.addEventListener('input', () => {
          if (audio.duration) {
            audio.currentTime = (seek.value / 100) * audio.duration;
          }
        });
        ['play', 'pause', 'timeupdate', 'loadedmetadata', 'ended'].forEach(
          (e) => audio.addEventListener(e, render)
        );
        render();
      });
    },
  };
})(Drupal, once);
