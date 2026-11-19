# Text link

Figma "Text link" (Components page): a standalone link, optionally with the 18px arrow.

- `tone`: `dark` on light surfaces, `light` on dark bands.
- `size`: `caps` (navigation, "All brands"), `body` (footer columns), `small` (footer legal row, inline error links).
- `underlined`: underlined at rest (Figma "Link/… · Underlined"); caps links drop to Link/Caps, 12px.
- `current: true`: the current page (Figma "Label/Nav · Active"). Underlined at rest at the link's own size, with `aria-current="page"`. A container can hide that underline with `--text-link-current-underline-color: transparent`, as the footer does.

The colour never changes on hover. The underline appears (or moves) to a 6px offset over 300ms.

Inline links inside body copy are styled by the base typography, not this component.
