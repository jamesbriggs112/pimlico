import EmblaCarousel from 'embla-carousel';
(function (once, Drupal) {
  Drupal.behaviors.emblaBehavior = {
    attach: function (context, settings) {
      once('emblaBehavior', 'div.embla', context).forEach(function (rootNode) {
        // Grab wrapper nodes
        const viewportNode = rootNode.querySelector('.embla__viewport');

        // Grab button nodes
        const prevButtonNode = rootNode.querySelector('.embla__prev');
        const nextButtonNode = rootNode.querySelector('.embla__next');

        // Initialize the carousel
        const embla = EmblaCarousel(viewportNode);

        // Add click listeners
        prevButtonNode.addEventListener('click', embla.scrollPrev, false);
        nextButtonNode.addEventListener('click', embla.scrollNext, false);

        const setNavTopOffset = () => {
          // find the tallest image in swiperElement and set css var to its height on the parent swiper container
          const images = viewportNode.querySelectorAll('img');
          let maxHeight = 0;
          images.forEach((img) => {
            if (img.clientHeight > maxHeight) {
              maxHeight = img.clientHeight;
            }
          });

          rootNode.style.setProperty(
            '--embla-navigation-top-offset',
            `${maxHeight / 2}px`
          );
        };

        embla.on('init', function () {
          setNavTopOffset();
        });

        embla.on('resize', function () {
          setNavTopOffset();
        });
      });
    },
  };
})(once, Drupal);
