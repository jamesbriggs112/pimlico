import EmblaCarousel from 'embla-carousel';

(function (once, Drupal) {
  Drupal.behaviors.contentCarousel = {
    attach(context) {
      once(
        'contentCarousel',
        '[data-component-id="four_starterkit:content_carousel"]',
        context
      ).forEach((rootNode) => {
        const viewportNode = rootNode.querySelector(
          '.content-carousel__viewport'
        );

        let options = { align: 'start' };

        const prefersReducedMotionQuery = window.matchMedia(
          '(prefers-reduced-motion: reduce)'
        );
        const prefersReducedMotion = prefersReducedMotionQuery.matches;

        if (prefersReducedMotion) {
          options = { ...options, duration: 0 };
        }

        // Initialize the carousel
        const embla = EmblaCarousel(viewportNode, options);

        const prevButtonNode = rootNode.querySelector(
          '.content-carousel__prev'
        );
        const nextButtonNode = rootNode.querySelector(
          '.content-carousel__next'
        );

        // Add click listeners
        prevButtonNode.addEventListener('click', embla.scrollPrev, false);
        nextButtonNode.addEventListener('click', embla.scrollNext, false);

        // disable prev button when on first slide and next button when on last slide
        const updateAriaLabels = () => {
          prevButtonNode.disabled = !embla.canScrollPrev();
          nextButtonNode.disabled = !embla.canScrollNext();

          if (embla.canScrollPrev()) {
            prevButtonNode.removeAttribute('disabled');
            prevButtonNode.setAttribute('aria-label', `Show previous slide`);
          } else {
            prevButtonNode.setAttribute('disabled', 'disabled');
            prevButtonNode.setAttribute(
              'aria-label',
              'You are on the first slide'
            );
          }

          if (embla.canScrollNext()) {
            nextButtonNode.removeAttribute('disabled');
            nextButtonNode.setAttribute('aria-label', `Show next slide`);
          } else {
            nextButtonNode.setAttribute('disabled', 'disabled');
            nextButtonNode.setAttribute(
              'aria-label',
              'You are on the last slide'
            );
          }
        };

        // Update aria-labels on init
        updateAriaLabels();

        // Update aria-labels on scroll
        embla.on('select', updateAriaLabels);
      });
    },
  };
})(once, Drupal);
