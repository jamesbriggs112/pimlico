import { disableBodyScroll, enableBodyScroll } from 'body-scroll-lock';

const MobileNav = () => {
  document.addEventListener('DOMContentLoaded', () => {
    const menuEl = document.querySelector('.site-header');
    const menuButton = document.querySelector('.hamburger');
    const pageBody = document.getElementsByTagName('body')[0];

    if (!menuButton) {
      return;
    }

    menuButton.addEventListener('click', () => {
      menuButton.classList.toggle('is-active');
      pageBody.classList.toggle('mobile-nav-open');

      disableBodyScroll(menuEl);

      if (pageBody.classList.contains('mobile-nav-open')) {
        disableBodyScroll(menuEl);
      } else {
        enableBodyScroll(menuEl);
      }
    });
  });
};

export default MobileNav;
