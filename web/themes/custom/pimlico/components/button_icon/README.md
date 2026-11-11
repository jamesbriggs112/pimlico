# Button Icon (media control)

Figma "Media control" (Components page): a 48px outlined circle with an icon. Hover fills the circle and inverts the icon.

- `tone: light` (default): white outline, for films, photos and dark bands.
- `tone: dark`: dark outline, for light surfaces such as the mobile gallery arrows.

`title` is the accessible name and is required ("Pause film", "Next image"). Use `pressed` for toggles. Pass `icon_name` with an icon name, or fill the `icon` slot. To resize, set `--button-icon-size` on the element.
