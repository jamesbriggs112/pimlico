# Front-end conventions

How the Pimlico Road theme (`web/themes/custom/pimlico`) is written. These follow the Four Drupal skeleton and the Sheffield Chamber reference build; [TAD Appendix A and B](TAD.md#appendix-a-build-conventions) record where Pimlico goes further.

## Components

Every visual pattern is a Single Directory Component in `components/<name>/` ([ADR 0006](adr/0006-build-every-visual-pattern-as-a-single-directory-component.md)). Scaffold one with:

```bash
vendor/bin/drush generate four-sdc
```

A component has `<name>.component.yml`, `<name>.twig`, `<name>.scss`, and where needed `<name>.js`, `<name>.stories.twig`, `README.md` and `thumbnail.png`.

- Props are snake_case and every prop has a `title`; give the component a `description` that names its Figma frame.
- Templates in `templates/` only compose components: `{% include 'pimlico:<name>' with { … } only %}`. Preprocess in `includes/*.inc` prepares the props.
- Include a button with Twig (`include('pimlico:button', …)`), not a `#type => component` render array: core's `#variant` would overwrite the button's `variant` prop.

## CSS naming: BEM

- **Block:** the component name in kebab-case. `components/policy_content/` gives `.policy-content`.
- **Element:** `__`, as in `.policy-content__title`.
- **Modifier:** `--`, as in `.btn--yellow`. State classes are `is-*`, as in `.is-active`.
- Keep to block and element: `block__element__subelement` is too deep. Use `&` in Sass, and keep selectors inside the component.

```scss
.policy-content {
  &__title { … }
  &--contents { … }
}
```

## Design tokens

Custom properties live in `src/sass/init/_root.scss`, in three layers:

1. **Brand:** the palette, `--color-brand-*` and `--color-tint-*`.
2. **Semantic:** what a colour is for, such as `--color-text-primary`, `--color-surface-tint`, `--color-text-eyebrow`. Components use these.
3. **Component:** for example `--button-background-color-dark` or `--header-bg-color`.

Spacing uses `--space-12`, `--space-16`, `--space-24` and `--space-32`; section padding uses `--section-space`.

## Breakpoints

[sass-mq](https://github.com/sass-mq/sass-mq), set in `src/sass/init/_mq-settings.scss`: `tablet` (768px) and `desktop` (980px). Write mobile first:

```scss
.card-grid__items {
  @include mq($from: desktop) { … }
}
```

## Sizes in rem

Use [sass-rem](https://github.com/pierreburel/sass-rem) for pixel values from Figma: `#{to.rem(16px)}`. Vite injects `sass-rem` (as `to`), `sass-mq` and the mixins into every component's Sass, so components need no `@use` lines.

## Mixins

In `src/sass/init/_mixins.scss`:

- **`fluid($min, $max)`** scales a value between the mobile and desktop page widths: `gap: #{fluid(24px, 40px)};`.
- **`pageContainer`** is the page width with its gutters.
- **`contentWithGutters`** gives a grid with `content` (reading width), `popout` (page width) and `full` columns. Use `grid-column: content`, or the `.narrow` and `.fullwidth` classes.
- **`contentGrid`** is a grid with no breakpoints: set `--grid-column-count` and `--grid-item--min-width`.
- **`title($size)`** is the Fraunces heading style.

## JavaScript

- One behaviour per component, named after it in camelCase (`Drupal.behaviors.cardGrid`), in an IIFE that takes `Drupal` and `once`.
- Use `once('<component-name>', selector, context)` with a kebab-case key.
- Respect `prefers-reduced-motion` for anything that moves.
- Tests go in `tests/js/<component>.test.js` (Vitest and jsdom; `drupal-harness.js` supplies `Drupal` and `once`). Run them with `pnpm test`.

## Images

Images are served whole and cropped by the browser with `object-fit: cover`. Every `<img>` uses `object-position: var(--focal-point, 50% 50%)`, and `includes/image.inc` sets `--focal-point` from the editor's focal point. A component that positions its image differently should keep the focal point first, as in `object-position: var(--focal-point, bottom)`.

## Accessibility

The site targets WCAG 2.2 AA.

- Links and buttons have descriptive names. Icon buttons use the `visually_hidden` component for their text.
- Everything focusable shows a focus state: replace any `outline: none` with `:focus-visible` and `box-shadow: var(--focus-ring)`.
- Use native elements where you can (`<details>`, `<dialog>`, `<button>`).
- Turn off movement for `prefers-reduced-motion: reduce`.
- Eyebrows use pumpkin `#e87400` at the client's request, which is 3:1 on white, below AA for small text ([TAD](TAD.md)).

## Tests and checks

```bash
vendor/bin/phpcs
vendor/bin/phpunit --testsuite custom    # modules and the theme's Kernel tests
cd web/themes/custom/pimlico && pnpm lint && pnpm test
```
