import Swiper from 'swiper';
import { Navigation, A11y } from 'swiper/modules';

(function (once, Drupal) {
  Drupal.behaviors.swiperBehavior = {
    attach: function (context, settings) {
      once('swiperBehavior', 'div.swiper-container', context).forEach(
        function (element) {
          const swiperElement = element.querySelector('.swiper');
          const config = JSON.parse(element.dataset.swiperConfig);

          const swiper = new Swiper(swiperElement, {
            init: false,
            speed: 300,
            modules: [Navigation, A11y],
            watchOverflow: true,
            navigation: {
              nextEl: element.querySelector('.swiper-button-next'),
              prevEl: element.querySelector('.swiper-button-prev'),
            },
            a11y: {
              enabled: true,
            },
            ...config,
          });

          const setNavTopOffset = () => {
            // find the tallest image in swiperElement and set css var to its height on the parent swiper container
            const images = swiperElement.querySelectorAll('img');
            let maxHeight = 0;
            images.forEach((img) => {
              if (img.clientHeight > maxHeight) {
                maxHeight = img.clientHeight;
              }
            });

            element.style.setProperty(
              '--swiper-navigation-top-offset',
              `${maxHeight / 2}px`
            );
          };

          swiper.on('init', function () {
            setNavTopOffset();
          });

          swiper.on('resize', function () {
            setNavTopOffset();
          });

          swiper.init();
        }
      );
    },
  };
})(once, Drupal);
