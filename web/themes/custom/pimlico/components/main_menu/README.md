# Main Menu

The header navigation, rendered from the `main` menu through `templates/menu/menu--advanced-mega-menu.html.twig`.

- **Desktop:** the first `split` items (default 3) sit left of the logo and the rest right, as in Figma "Header". The current section is underlined.
- **Items with children** become a disclosure button with a mega menu panel (`mega_menu`). The children are the panel's primary links, and Advanced Mega Menu rows (Views and blocks set up under Structure » Menus » Main navigation) add the further columns.
- **Mobile:** the same markup is the accordion in the mobile menu (Figma "Mobile nav item"). The panel shows only the primary links.

The menu link description, if set, is the panel's eyebrow ("Explore the street"); otherwise the item title is used. `header.js` handles opening and closing.
