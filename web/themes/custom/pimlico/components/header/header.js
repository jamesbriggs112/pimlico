/**
 * @file
 * Header behaviours: the mobile menu overlay and hide-on-scroll.
 *
 * Follows the reference theme's pattern: behaviours keyed per element with
 * once(); Escape closes the mobile menu and returns focus.
 */
(function (Drupal, once) {
  const desktop = window.matchMedia('(min-width: 61.25em)');

  // Content outside the header that is hidden from everyone while the
  // mobile menu covers the page.
  const pageRegions = (header) =>
    [...document.querySelectorAll('main, footer, .newsletter-region')].filter(
      (el) => !header.contains(el)
    );

  const setMobileMenu = (header, open) => {
    const toggle = header.querySelector('.header__menu-toggle');
    // The menu opens below the header bar, wherever the bar sits now.
    if (open) {
      header.style.setProperty(
        '--header-nav-top',
        `${Math.max(0, header.getBoundingClientRect().bottom)}px`
      );
    }
    header.classList.toggle('header--nav-open', open);
    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    pageRegions(header).forEach((el) => (el.inert = open));
  };

  Drupal.behaviors.header = {
    attach(context) {
      // Hide on scroll down, show on scroll up. Past the header's own
      // height the overlay header (hero pages) is pinned as the solid
      // header; at the top it goes back over the hero. Focus inside the
      // header always shows it, for keyboard users.
      once('header-scroll', '.header', context).forEach((header) => {
        let lastY = window.scrollY;
        let ticking = false;
        const update = () => {
          const y = window.scrollY;
          const height = header.offsetHeight;
          if (y <= height) {
            header.classList.remove('header--hidden', 'header--pinned');
          } else if (y > lastY + 4) {
            header.classList.add('header--hidden');
            header.classList.add('header--pinned');
          } else if (y < lastY - 4) {
            header.classList.add('header--pinned');
            header.classList.remove('header--hidden');
          }
          lastY = y;
          ticking = false;
        };
        window.addEventListener(
          'scroll',
          () => {
            if (!ticking) {
              ticking = true;
              window.requestAnimationFrame(update);
            }
          },
          { passive: true }
        );
        header.addEventListener('focusin', () =>
          header.classList.remove('header--hidden')
        );
      });

      once('header', '.header', context).forEach((header) => {
        const menuToggle = header.querySelector('.header__menu-toggle');
        menuToggle?.addEventListener('click', () =>
          setMobileMenu(header, !header.classList.contains('header--nav-open'))
        );

        // In-page links in the mobile menu (newsletter) close it first.
        header
          .querySelector('.header__newsletter-link')
          ?.addEventListener('click', () => {
            if (header.classList.contains('header--nav-open')) {
              setMobileMenu(header, false);
            }
          });

        header.addEventListener('keydown', (event) => {
          if (event.key !== 'Escape') {
            return;
          }
          if (header.classList.contains('header--nav-open')) {
            setMobileMenu(header, false);
            menuToggle?.focus();
          }
        });

        // Leaving the mobile layout closes the overlay.
        desktop.addEventListener('change', (event) => {
          if (event.matches && header.classList.contains('header--nav-open')) {
            setMobileMenu(header, false);
          }
        });
      });
    },
  };
})(Drupal, once);
