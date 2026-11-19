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
        Drupal.pimlico.scrollTrack(
          track,
          prev,
          next,
          ':scope > *',
          (overflows) => {
            controls.hidden = !overflows;
          }
        );
      });
    },
  };
})(Drupal, once);
