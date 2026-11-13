# Icon

Decorative icons from the Figma icon set ("Icon / *" on the Components page), drawn in `currentColor`.

## Usage

```twig
{% include "pimlico:icon" with { name: "search" } only %}
```

Icons are `aria-hidden`. The control that contains one must have its own accessible name. To add an icon, copy its path from Figma into the `icons` map in `icon.twig` and add the name to the enum in `icon.component.yml`.
