# Pimlico Road: what changed and why

The site is in good shape. The PHP passes Drupal's coding standards, the custom code has real tests, and the documentation is better than on most of our projects. Most of the changes below are habits that make code easier for the next person to read and change, not mistakes.

A lot of the deleted code came from the starterkit and skeleton, not from you. It's worth knowing it was there, but it isn't your fault.

## 1. Don't repeat yourself

When the same code exists in two or more places, a later change has to be made in all of them, and sooner or later one copy gets missed.

**Example: the scroll arrows.** `card_grid.js` and `image_gallery.js` had the same 30 lines of scrolling code. The two copies had already drifted apart: one read `scrollPaddingLeft` and the other `paddingLeft`, so one of them was quietly wrong. That code is now one shared helper, `src/js/scroll-track.js`, and each component calls it in one line.

**Example: the eyebrow style.** The five lines for the caps "eyebrow" label were typed out by hand in about eight components. They're now one mixin:

```scss
// Before, in eight files
font-size: var(--font-size-label-eyebrow);
font-weight: var(--font-weight-bold);
line-height: var(--line-height-label);
letter-spacing: var(--letter-spacing-label-eyebrow);
text-transform: uppercase;

// After
@include eyebrow;
```

The link underline and the mobile swipe track got the same treatment.

**Rule of thumb:** the second time you copy a block, stop and ask whether it should be a mixin, a component or a function. Three copies almost always means yes.

## 2. Use the design tokens you have

`--max-text-width` already existed, but `760px` was still typed out in 11 places. The spacing scale had only four steps, so most spacing was hard-coded (`to.rem(40px)` and so on).

There's now a full spacing scale (`--space-4` to `--space-128`, like HCRW's). Spacing uses it wherever the Figma value matches.

**Rule of thumb:** before typing a pixel value, check `_root.scss`. If the value is used more than once and there's no token for it, add one.

## 3. Name things so the next reader doesn't have to guess

Short names are quick to type but slow to read:

| Before | After |
|---|---|
| `{% set a = node.field_address.0 %}` | `{% set postal = node.field_address.0 %}` |
| `{% set h = 'h' ~ heading_level %}` | `{% set heading_tag = 'h' ~ heading_level %}` |
| `{% set attrs = … %}` | `{% set link_attributes = … %}` |
| `filter(v => v)` | `filter(value => value)` |

**Rule of thumb:** someone reading a line on its own should know what the variable holds. One-letter names are fine only for something like a loop counter.

## 4. Don't rely on values editors can change

The Brands filters, the Dine page and the search grouping all looked up the category by its name, `'Food & Drink'`, in five places. If an editor renamed that term, those pages would have quietly broken.

They now use the term's UUID, which stays the same on every environment. The rules live in one class, `BrandGroups`, and a test renames the categories to prove nothing breaks.

**Rule of thumb:** code should identify content by something stable (a UUID or machine name), never by text an editor can edit. And if a rule lives in five files, move it into one.

## 5. Let Drupal do what it already does

A few templates built by hand things Drupal already provides:

- **The Brand page breadcrumb was written out in Twig.** It now goes through Drupal's breadcrumb system, so it has the same markup, accessibility (`aria-current`) and structured data as every other page.
- **Email and phone links were built by hand** (`<a href="mailto:…">`). They now use Drupal's field formatters.
- **The podcast template read the audio file directly from the media entity.** That skipped Drupal's access check, so unpublished audio would still play. It also skipped the cache tags, so pages didn't update when the audio changed.

**Rule of thumb:** before writing markup for a field or a breadcrumb, check whether a formatter, display setting or Twig Tweak function already does it. Reading `.entity.something` in Twig is a warning sign: you lose access checks and cache tags.

## 6. Small bugs from passing data the wrong way

The mobile menu didn't close when you tapped "Sign up to the newsletter". The header passed the link a class through `attributes`, and the button component removes any class passed that way, so the JavaScript never found it. The fix was to use the button's `class` prop, which exists for this.

**Rule of thumb:** when you use a component, read its `component.yml` and pass data through the props it declares.

## 7. Keep files tidy and consistent

- Indentation is now 2 spaces everywhere. Twenty Twig files were using tabs, mostly from the starterkit.
- Component YAML now uses one style throughout.
- ESLint now has its recommended rules turned on, so it catches unused variables and `console.log` calls. It didn't before, and that's how a leftover `console.log` ran on every page.

**Rule of thumb:** run `pnpm lint` and `npx prettier --check` before committing. Let the tools catch the small things.

## 8. Remove code that isn't used

About 100 CSS variables, several components, templates and four theme regions weren't used anywhere. Most came from the starterkit. Unused code still costs the next reader time, because they have to work out whether it matters.

**Rule of thumb:** when you start from a starterkit, delete what you don't use as you go.

## 9. Keep the docs true

The docs were thorough, which is good. But a few facts had drifted from the code: the wrong category names, broken section references, and set-up steps that no longer worked. Some facts were also repeated in three or four documents, which is how they drift.

**Rule of thumb:** write each fact in one place and link to it from elsewhere. When you change code, search the docs for anything that describes it.

---

The one thing worth taking away: write code for the next person who has to read it. Before you finish something, ask whether someone new could find where a value comes from and change it in one place.
