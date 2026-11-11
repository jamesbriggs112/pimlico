/**
 * @file
 * Hero film: pause and play the background film (WCAG 2.2.2). Films don't
 * start for people who prefer reduced motion until they press play.
 */
(function (Drupal, once) {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  Drupal.behaviors.heroFilm = {
    attach(context) {
      once(
        'hero-film',
        '[data-component-id="pimlico:hero_film"]',
        context
      ).forEach((hero) => {
        const video = hero.querySelector('video');
        const toggle = hero.querySelector('[data-hero-film-toggle]');
        if (!video || !toggle) {
          return;
        }
        const label = toggle.querySelector('[data-hero-film-label]');
        const text = hero.querySelector('[data-hero-film-text]');

        const setPaused = (paused) => {
          hero.classList.toggle('hero-film--paused', paused);
          hero.classList.toggle('hero-film--playing', !paused);
          toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
          const words = paused ? Drupal.t('Play film') : Drupal.t('Pause film');
          label.textContent = words;
          text.textContent = words;
        };

        if (reducedMotion.matches) {
          video.pause();
          video.removeAttribute('autoplay');
          setPaused(true);
          hero.classList.remove('hero-film--playing');
        } else {
          setPaused(video.paused && !video.autoplay);
        }

        toggle.addEventListener('click', () => {
          if (video.paused) {
            video.play();
            setPaused(false);
          } else {
            video.pause();
            setPaused(true);
          }
        });
      });
    },
  };
})(Drupal, once);
