/**
 * @file
 * Card grid: on mobile the cards are a swipe track. Previous and next
 * buttons scroll it by one card, are disabled at either end, and show only
 * when there are more cards than fit.
 */
(function (Drupal, once) {
  Drupal.behaviors.cardGrid = {
    attach(context) {
      once('card-grid', '.card-grid', context).forEach((grid) => {
        const track = grid.querySelector('.card-grid__items');
        const controls = grid.querySelector('.card-grid__controls');
        const prev = grid.querySelector('[data-cards-prev]');
        const next = grid.querySelector('[data-cards-next]');
        if (!track || !controls || !prev || !next) {
          return;
        }
        const reducedMotion = window.matchMedia(
          '(prefers-reduced-motion: reduce)'
        );

        const step = (direction) => {
          const items = [...track.children];
          const left = track.scrollLeft;
          const padding =
            parseFloat(getComputedStyle(track).scrollPaddingLeft) || 0;
          const offset = (item) => item.offsetLeft - track.offsetLeft - padding;
          const target =
            direction > 0
              ? items.find((item) => offset(item) > left + 2)
              : items.reverse().find((item) => offset(item) < left - 2);
          track.scrollTo({
            left: target
              ? offset(target)
              : direction > 0
                ? track.scrollWidth
                : 0,
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
          });
        };

        const update = () => {
          const max = track.scrollWidth - track.clientWidth - 2;
          controls.hidden = max <= 0;
          prev.disabled = track.scrollLeft <= 2;
          next.disabled = track.scrollLeft >= max;
        };

        prev.addEventListener('click', () => step(-1));
        next.addEventListener('click', () => step(1));
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
      });
    },
  };
})(Drupal, once);
