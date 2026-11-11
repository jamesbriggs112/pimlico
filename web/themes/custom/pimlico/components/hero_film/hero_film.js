/**
 * @file
 * Hero film: the poster shows first and the film starts once the page has
 * loaded, so the film never delays the page's main image (LCP). It stays on
 * the poster, with a Play button, for people who prefer reduced motion, have
 * data saver on, or are on a slow connection. Pause and play meet WCAG 2.2.2.
 */
(function (Drupal, once) {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const connection = navigator.connection || {};
  const slow =
    connection.saveData ||
    ['slow-2g', '2g', '3g'].includes(connection.effectiveType);

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

        const play = () => {
          video.play().then(
            () => setPaused(false),
            () => setPaused(true)
          );
        };

        video.preload = 'none';
        setPaused(true);
        if (!reducedMotion.matches && !slow) {
          if (document.readyState === 'complete') {
            play();
          } else {
            window.addEventListener('load', play, { once: true });
          }
        }

        toggle.addEventListener('click', () => {
          if (video.paused) {
            play();
          } else {
            video.pause();
            setPaused(true);
          }
        });
      });
    },
  };
})(Drupal, once);
