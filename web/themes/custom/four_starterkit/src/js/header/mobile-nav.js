const MobileNav = () => {
  document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.querySelector('.hamburger');
    const pageBody = document.getElementsByTagName('body')[0];

    if (!menuButton) {
      return;
    }

    menuButton.addEventListener('click', () => {
      menuButton.classList.toggle('is-active');
      pageBody.classList.toggle('mobile-nav-open');
    });
  });
};

export default MobileNav;
