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
        if (track && prev && next) {
          Drupal.pimlico.scrollTrack(track, prev, next, '.image-gallery__item');
        }
      });
    },
  };
})(Drupal, once);
