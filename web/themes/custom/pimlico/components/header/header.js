/**
 * @file
 * Header behaviours: mega menu disclosures and the mobile menu overlay.
 *
 * Follows the reference theme's pattern: behaviours keyed per element with
 * once(), one mega menu open at a time, Escape closes and returns focus.
 */
(function (Drupal, once) {
  const desktop = window.matchMedia('(min-width: 61.25em)');

  const panelFor = (toggle) =>
    document.getElementById(toggle.getAttribute('aria-controls'));

  const setPanel = (toggle, open) => {
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    panelFor(toggle)?.toggleAttribute('hidden', !open);
  };

  const closePanels = (scope, except = null) => {
    scope
      .querySelectorAll('.main-menu__toggle[aria-expanded="true"]')
      .forEach((toggle) => toggle !== except && setPanel(toggle, false));
  };

  // Content outside the header that is hidden from everyone while the
  // mobile menu covers the page.
  const pageRegions = (header) =>
    [...document.querySelectorAll('main, footer, .newsletter-region')].filter(
      (el) => !header.contains(el)
    );

  const setMobileMenu = (header, open) => {
    const toggle = header.querySelector('.header__menu-toggle');
    header.classList.toggle('header--nav-open', open);
    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    pageRegions(header).forEach((el) => (el.inert = open));

    if (open) {
      header.querySelector('.header__menu-close')?.focus();
    } else {
      closePanels(header);
      toggle?.focus();
    }
  };

  Drupal.behaviors.header = {
    attach(context) {
      // Keyed per toggle so menu markup that arrives later (BigPipe) still
      // gets its behaviour.
      once('header-mega-menu', '.main-menu__toggle', context).forEach(
        (toggle) => {
          const header = toggle.closest('.header') || document;
          toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            closePanels(header, toggle);
            setPanel(toggle, open);
          });
        }
      );

      once('header', '.header', context).forEach((header) => {
        header
          .querySelector('.header__menu-toggle')
          ?.addEventListener('click', () => setMobileMenu(header, true));

        header
          .querySelector('.header__menu-close')
          ?.addEventListener('click', () => setMobileMenu(header, false));

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
          const open = header.querySelector(
            '.main-menu__toggle[aria-expanded="true"]'
          );
          if (open && desktop.matches) {
            setPanel(open, false);
            open.focus();
          } else if (header.classList.contains('header--nav-open')) {
            setMobileMenu(header, false);
          }
        });

        // Close a desktop panel when focus or a click leaves the header.
        document.addEventListener('click', (event) => {
          if (desktop.matches && !header.contains(event.target)) {
            closePanels(header);
          }
        });
        header.addEventListener('focusout', (event) => {
          if (desktop.matches && !header.contains(event.relatedTarget)) {
            closePanels(header);
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
