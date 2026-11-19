# Header

Figma "Header": split navigation either side of the logo on desktop; menu button, logo and search on mobile, where the menu opens as an overlay. `overlay: true` makes it transparent over a film hero until the page scrolls.

`header.js` opens and closes the mobile menu (the toggle, Escape, or following the newsletter link) and hides the header on scroll down. The `primary_menu` and `secondary_menu` blocks take the menus from `page.html.twig`.
