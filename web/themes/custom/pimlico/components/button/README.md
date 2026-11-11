# Button

Figma "A / Button" (Components page). Two variants:

- `dark`: dark-grey fill, white label. The default action.
- `yellow`: yellow fill, dark label. The lead action on dark bands and photos, or the second of a pair.

Hover is a mustard fill with a dark label for both. Focus is the two-tone focus ring.

## Usage

```twig
{% include "pimlico:button" with { text: "Discover every brand", url: "/brands", variant: "dark" } only %}
```

Renders a link when `url` is set, otherwise a `<button>`. `full_width` stretches it to its container, as the mobile designs do. The starterkit `color` prop still works (`primary` is dark, `secondary` is yellow) so existing templates keep rendering.
