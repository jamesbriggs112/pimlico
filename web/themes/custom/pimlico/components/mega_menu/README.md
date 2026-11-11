# Mega menu

Figma "Mega menu": the full-width panel that drops below the header when a top-level item with children is opened.

- **Primary column:** the menu item's children in the large serif style (Figma "Menu link"), under an eyebrow taken from the menu link description.
- **Further columns** (the "Featured" or "Latest" list and the feature card) come from Advanced Mega Menu rows, set up per menu item under Structure » Menus » Main navigation.
- **Mobile:** inside the mobile menu, only the primary links show, as the accordion body.

The panel starts `hidden`. `header.js` toggles it from the button that `aria-controls` it.
