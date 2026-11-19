/**
 * @file
 * A swipe track with previous and next buttons, shared by the card grid and
 * the image gallery. The buttons scroll by one item and are disabled at
 * either end.
 */
(function (Drupal) {
  Drupal.pimlico = Drupal.pimlico || {};

  /**
   * Wires previous and next buttons to a horizontally scrolling track.
   *
   * @param {HTMLElement} track
   *   The scrolling element. It must be the items' offsetParent (position:
   *   relative), so an item's offsetLeft is the scroll position that shows it.
   * @param {HTMLButtonElement} prev
   *   The previous button.
   * @param {HTMLButtonElement} next
   *   The next button.
   * @param {string} itemSelector
   *   Selects the items to step between, within the track.
   * @param {function(boolean)} [onUpdate]
   *   Called on every update with whether the items overflow the track.
   */
  Drupal.pimlico.scrollTrack = (track, prev, next, itemSelector, onUpdate) => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const step = (direction) => {
      const items = [...track.querySelectorAll(itemSelector)];
      const left = track.scrollLeft;
      // Snapping aligns items to the scroll padding, not the padding.
      const padding =
        parseFloat(getComputedStyle(track).scrollPaddingLeft) || 0;
      const offset = (item) => item.offsetLeft - padding;
      const target =
        direction > 0
          ? items.find((item) => offset(item) > left + 2)
          : items.reverse().find((item) => offset(item) < left - 2);
      track.scrollTo({
        left: target ? offset(target) : direction > 0 ? track.scrollWidth : 0,
        behavior: reducedMotion.matches ? 'auto' : 'smooth',
      });
    };

    const update = () => {
      const max = track.scrollWidth - track.clientWidth - 2;
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = track.scrollLeft >= max;
      onUpdate?.(max > 0);
    };

    prev.addEventListener('click', () => step(-1));
    next.addEventListener('click', () => step(1));
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
  };
})(Drupal);
