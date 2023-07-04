import { register } from 'swiper/element/bundle';
import { Navigation } from 'swiper';
// register Swiper custom elements
register();

const swipers = document.querySelectorAll('swiper-container');

function getParams(slidesPerView) {
  let breakpoints = {
    320: {
      slidesPerView: 1.2,
    },
    768: {
      slidesPerView: 2,
    },
    1024: {
      slidesPerView: 3,
    },
  };

  if (slidesPerView === 1) {
    breakpoints = {
      320: {
        slidesPerView: 1,
      },
    };
  }

  // swiper parameters
  return {
    modules: [Navigation],
    slidesPerView: 'auto',
    spaceBetween: 30,
    navigation: true,
    breakpoints,
    watchOverflow: true,
    watchSlidesProgress: true,
    injectStylesUrls: [
      '/themes/custom/four/css/components/paragraph-image-carousel.css',
    ],
  };
}

document.addEventListener('DOMContentLoaded', () => {
  swipers.forEach((el) => {
    const changeNavOffset = el.parentElement.getAttribute(
      'data-change-nav-offset'
    );
    const slidesPerView =
      el.parentElement.getAttribute('data-slides-per-view') ?? 3;
    Object.assign(el, getParams(Number(slidesPerView)));
    el.initialize();

    function setOffset() {
      const swiperHeight = el.clientHeight;
      const imageHeight = el.querySelector('img').clientHeight;
      const offset = ((imageHeight - 8) / swiperHeight) * 100;
      el.parentElement.style.setProperty(
        '--swiper-navigation-top-offset',
        `${offset}%`
      );
    }

    if (changeNavOffset) {
      setOffset();
      window.onresize = setOffset;
    }
  });
});
