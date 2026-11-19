# Footer

Figma "Footer" (7:108): the link columns on top, then a ruled bottom row with the legal links and copyright on the left and the Pimlico Road and Grosvenor logos bottom right, aligned to the bottom. On mobile the columns stack, the legal links and copyright are centred, and the logos come last, centred.

- `menu`: the footer_menu region (menu blocks whose labels show as the column titles).
- `bottom`: the footer_bottom region (the legal menu, then the copyright block).

The logos are part of the component. The current page's link isn't underlined in the footer: the footer sets `--text-link-current-underline-color: transparent` (it keeps `aria-current`).
