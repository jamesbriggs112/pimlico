/**
 * @file
 * Image gallery: previous and next buttons scroll the mobile track by one
 * photograph, and are disabled at either end.
 */
(function (Drupal, once) {
  Drupal.behaviors.imageGallery = {
    attach(context) {
      once(
        'image-gallery',
        '[data-component-id="pimlico:image_gallery"]',
        context
      ).forEach((gallery) => {
        const track = gallery.querySelector('.image-gallery__track');
        const prev = gallery.querySelector('[data-gallery-prev]');
        const next = gallery.querySelector('[data-gallery-next]');
        if (!track || !prev || !next) {
          return;
        }
        const reducedMotion = window.matchMedia(
          '(prefers-reduced-motion: reduce)'
        );

        const step = (direction) => {
          const items = [...track.querySelectorAll('.image-gallery__item')];
          const left = track.scrollLeft;
          const target =
            direction > 0
              ? items.find(
                  (item) => item.offsetLeft - track.offsetLeft > left + 2
                )
              : items
                  .reverse()
                  .find(
                    (item) => item.offsetLeft - track.offsetLeft < left - 2
                  );
          track.scrollTo({
            left: target
              ? target.offsetLeft -
                track.offsetLeft -
                parseFloat(getComputedStyle(track).paddingLeft)
              : direction > 0
                ? track.scrollWidth
                : 0,
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
          });
        };

        const update = () => {
          const max = track.scrollWidth - track.clientWidth - 2;
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
