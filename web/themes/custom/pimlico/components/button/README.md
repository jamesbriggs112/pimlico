# Button

Figma "A / Button" (Components page). Two variants:

- `yellow`: yellow fill, dark-grey label. The default, and the only style the site uses. Hover turns it dark grey (`--color-surface-inverse`) with a 2px yellow border and a yellow label, so on a dark section the yellow border still outlines it.
- `dark`: dark-grey fill, white label. Still in the design system but unused. Hover adds a yellow border and turns the label yellow.

The border is drawn inside the 56px height at rest too (in the fill colour), so hovering never moves anything. Hover eases over 150ms. Focus is the two-tone focus ring.

## Usage

```twig
{% include "pimlico:button" with { text: "Discover every brand", url: "/brands" } only %}
```

Renders a link when `url` is set, otherwise a `<button>`. `full_width` stretches it to its container, as the mobile designs do. `external` opens the link in a new tab and adds visually hidden "(opens in a new tab)" text to its name. Drupal form submits (`templates/form/input--submit.html.twig`) and the skip link use it too, so they are Yellow.
