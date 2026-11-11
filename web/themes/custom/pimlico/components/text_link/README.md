# Text link

Figma "Text link" (Components page): a standalone link, optionally with the 18px arrow.

- `tone`: `dark` on light surfaces, `light` on dark bands.
- `size`: `caps` (navigation, "All brands"), `body` (footer columns), `small` (footer legal row, inline error links).
- `underlined`: underlined at rest, for the current navigation or footer item. `current: true` does the same and adds `aria-current="page"`.

The colour never changes on hover. The underline appears (or moves) to a 6px offset over 300ms.

Inline links inside body copy are styled by the base typography, not this component.
