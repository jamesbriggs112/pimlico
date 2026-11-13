# Code quality

How to write, review and refactor code in this repository so the next developer can read it and change it in one place. Apply it to every change, not only to clean-up work. Front-end specifics (tokens, breakpoints, mixins, JS conventions) are in [front-end-conventions.md](../front-end-conventions.md); this page does not repeat them.

## Order of work

1. **Take a baseline before changing anything.** Save the rendered pages and the compiled CSS (see [Verification tools](#verification-tools)). A refactor is only proven behaviour-preserving against a baseline taken first.
2. **Subtract before you add.** Delete dead code, then refactor what is left. Removing first makes the remaining structure, and the refactor, smaller.
3. **Work in small steps and check each one.** After each step: `drush cim` if config changed, `drush cr`, a new page snapshot, and a comparison against the previous step. Fix or explain every difference before the next step.
4. **Finish with the full checks** in [Before you finish](#before-you-finish).
5. **Report with evidence.** List what changed, what output changed on purpose, what you checked and how, and what you chose not to change and why.

## Don't repeat yourself

The second copy of a block is the time to stop and look for a shared home. By the third, share it. Copies drift: when `card_grid.js` and `image_gallery.js` each had their own scrolling code, one read `scrollPaddingLeft` and the other `paddingLeft`, and only one was right.

Where shared code lives:

| What | Where |
|---|---|
| Repeated declaration blocks | A mixin in `src/sass/init/_mixins.scss` (Vite injects it into every stylesheet) |
| Repeated values | A custom property in `src/sass/init/_root.scss` |
| Repeated markup | A component in `components/`; templates include it |
| Repeated JS | A helper in `src/js/`, registered as a library in `pimlico.libraries.yml`, listed under the component's `libraryOverrides.dependencies` (see `scroll-track`) |
| Repeated PHP rules | One service that owns the rule (see `BrandGroups`), injected wherever it is needed |
| Repeated per-bundle Twig logic | One general preprocess function, e.g. `pimlico_preprocess_paragraph()` for anchors, rather than the same lines in each bundle template |

Don't share code that only looks alike. Three node templates that each pass a `card_grid` a different heading are adapters, not duplication. If extracting a block makes the reader jump to another file to understand one line, leave it inline.

## CSS custom properties and tokens

- Use CSS custom properties for every design value. Sass variables only for build-time values custom properties can't hold: sass-mq breakpoints, the font map in `_fonts.scss`, data-URI SVGs.
- Check `_root.scss` before typing a value. Spacing uses the `--space-<px>` scale; text columns use `--max-text-width`; motion uses `--motion-duration` and `--motion-easing`. Use `#{to.rem(…)}` only when no token matches.
- A value used twice with no token gets one.
- Components use semantic colours (`--color-text-primary`), not brand colours (`--color-brand-pumpkin`). For a transparent version of a palette colour, use `color-mix(in srgb, var(--color-…) N%, transparent)` rather than writing its RGB values out.
- Delete tokens nothing reads. Before deleting one, search `components`, `src`, `templates`, `includes` and `config/sync` for it; Plyr reads `--plyr-*` from outside the theme.

## Naming

- A name should tell a reader what it holds without reading the surrounding code. No single letters except loop counters: `postal`, not `a`; `heading_tag`, not `h`; `link_attributes`, not `attrs`; `filter(value => value)`, not `filter(v => v)`.
- CSS: the BEM block is the component's name (`.story-card`, `.story-card__title`). A component's stylesheet styles only its own block. Don't style another component's elements from a layout partial or another component; give that component a modifier or prop instead.
- JS: one behaviour per component, named after it in camelCase (`Drupal.behaviors.cardGrid`); `once()` keys are the kebab-case component name (`'card-grid'`).
- PHP: follow TAD Appendix A. Services are named by class; hook classes live in `src/Hook/*Hooks.php`.

## Identify content by something stable

Code must not depend on text an editor can change. The Brands filters, Dine and search grouping once matched the term name `'Food & Drink'` in five places; renaming the term would have broken them all without an error.

- Taxonomy terms: use the term UUID. Structure Sync keeps UUIDs the same on every environment, but term IDs differ (Interiors is 1 in config and 7 on a fresh local install). The Brand Category UUIDs are constants on `BrandGroups`.
- Config entities: use the machine name.
- Never compare labels, titles or term names in code or templates.
- Keep each such rule in one class and test it the way it fails: `BrandGroupsTest::testRenamedCategories` renames the terms and checks nothing breaks.

## Let Drupal render, check access and cache

Reaching through `node.field_x.entity.something` in Twig skips the access check and the cache tags that rendering the field would add. Before writing markup for a field, use, in this order:

1. The rendered field (`content.field_x`) with the formatter set in the display config: `email_mailto`, `telephone_link`, `address_plain`, entity reference formatters.
2. Twig Tweak: `drupal_block`, `drupal_view`, `drupal_menu`, `|children` (render-array children; don't filter on `#` keys by hand).
3. A theme component, included with `only`.
4. Preprocess PHP, only for a requirement 1 to 3 can't meet, named in its docblock.

Values from the node's own fields (`node.field_summary.value`) are fine for plain text and for building URLs: the node's cache tag covers them.

Breadcrumbs come from Drupal's breadcrumb system. Print them with `drupal_block('system_breadcrumb_block')`. For a trail the path doesn't give, write a breadcrumb builder (see `BrandBreadcrumbBuilder`) rather than markup in a template.

When preprocess PHP must load or check something itself:

```php
$access = $media->access('view', NULL, TRUE);
$cache = CacheableMetadata::createFromRenderArray($variables)
  ->addCacheableDependency($media)
  ->addCacheableDependency($access);
if ($access->isAllowed()) {
  // …
}
$cache->applyTo($variables);
```

- Use `access(…, TRUE)` to get an `AccessResult` you can add as a cache dependency. A plain boolean loses its cache contexts.
- Add every loaded entity as a dependency, and apply the metadata even on the early-return path.
- Use `Url::toString(TRUE)` and add the result as a dependency when building URLs in PHP.

## Components

- Read the `component.yml` before using a component, and pass data through the props it declares. `button` and `button_icon` discard any `class` passed in `attributes`, so a class meant for JavaScript must use the `class` prop. Passing it through `attributes` once broke the mobile menu without any error.
- Include with `only` so the component can't pick up the caller's variables, the `<body>`'s `attributes` for example.
- Give every component a `description`, a `title` on every prop, and a README that says what it is and where it's used. Write component YAML in block style, not inline `{ type: …, title: … }`.

## Dead code

Delete what nothing uses. Prove it's unused first:

- Search for the name in `templates`, `components`, `includes`, `src`, `web/modules/custom`, `config/sync`, `scripts` and `docs`.
- For templates, check the suggestion can render: which bundles and view modes exist (`core.entity_view_display.*`), which views use which row plugin, what Search API renders (`search_api.index.*`).
- For regions, check `page.html.twig` prints them, and delete blocks placed in regions that aren't printed (`config/sync/block.block.*`).
- Compare page snapshots after deleting. Deleting dead code changes no output.

Don't delete without care:

- **Post-update functions.** A `post_update` that hasn't run on staging or production yet carries a data fix. Leave them unless you know every environment has run them.
- **Files that look like boilerplate.** `src/stories/images` is used by `scripts/dev/seed-pages.php` and by component stories even though the rest of `src/stories` was Storybook's example code.
- **Shared skeleton code.** `four_sdc`, `custom_image_styles`, `cloudfront_cache_home_page`, `scaffold/*` and starterkit components are shared with other Four projects. Fix them upstream in the skeleton too, and say so in your report.

## Formatting

- Two-space indentation in every file type, including Twig (the starterkit used tabs). No trailing whitespace; a final newline.
- `npx prettier --check` and `npx eslint .` from the theme directory pass. ESLint runs the recommended rules, so unused variables and stray `console.log` calls fail.
- `vendor/bin/phpcs` (Drupal and DrupalPractice) passes on custom modules and the theme.
- Comments explain why, not what. A docblock on every PHP class, function and Twig template (`{# @file #}`) says what it is for. Delete comments the code has outgrown; a stale comment is worse than none.

## Tests

- Test behaviour through the public surface, and assert literal expected values. A test that would still pass if the code returned `NULL` tests nothing.
- Kernel tests live in each module's `tests/src/Kernel`; JS behaviour tests in `web/themes/custom/pimlico/tests/js` (Vitest and jsdom; `drupal-harness.js` provides `Drupal` and `once`).
- Add a test for the bug you fix, written so it fails without the fix.
- A JS test with hand-written markup can't catch a Twig bug. When the bug is in rendered markup, also check the behaviour against the real page HTML (load the snapshot into jsdom, attach the behaviour, act, assert).

## Documentation

- Each fact lives in one place; other docs link to it. A fact repeated in several documents will drift.
- When you change code, search `docs`, the READMEs, `AGENTS.md` and `CONTEXT.md` for anything that describes it, and update it in the same change.
- ADRs are records. Don't rewrite a decision; add a dated "Update" note with the current facts.
- Write for people first: plain British English, short sentences, sentence-case headings.

## Verification tools

Run from the project root with PHP 8.4 first on `PATH`. The scripts compare output, so they only prove something against a baseline taken before the change.

| Script | What it does |
|---|---|
| `scripts/qa/snapshot-pages.sh <dir>` | Saves the HTML of every node page and the main listings, with per-request values masked |
| `scripts/qa/compare-pages.sh <before> <after> [-v]` | Lists pages whose markup differs, ignoring whitespace, Twig debug comments and random story cards; `-v` prints the diffs |
| `node scripts/qa/compile-css.mjs <dir>` | Compiles every SCSS entry point as Vite does, without touching `dist/` |
| `node scripts/qa/compare-css.mjs <before> <after>` | Diffs the compiled CSS by computed value, with `var()` resolved, so swapping a literal for an equal token shows no difference |

A typical refactor:

```sh
scripts/qa/snapshot-pages.sh /tmp/pages-before
node scripts/qa/compile-css.mjs /tmp/css-before
# … one step of the change, then drush cim / drush cr as needed …
scripts/qa/snapshot-pages.sh /tmp/pages-after
scripts/qa/compare-pages.sh /tmp/pages-before /tmp/pages-after -v
node scripts/qa/compile-css.mjs /tmp/css-after
node scripts/qa/compare-css.mjs /tmp/css-before /tmp/css-after
```

Every difference either goes or gets a reason in the report.

To prove a YAML reformat changed no data, compare the parsed file with `git show HEAD:<file>` using Symfony's YAML parser from `vendor/`.

The snapshots need content: run the local set-up in full ([local-development.md](../local-development.md)), including the Structure Sync imports and the Brand migration, or Brands have no categories and whole templates go untested. Content types with no seed data (Articles, Press Articles, Podcast Episodes) need a few sample nodes made locally before the baseline.

## Before you finish

- [ ] `vendor/bin/drush config:status` shows no differences
- [ ] `vendor/bin/phpcs` passes
- [ ] `vendor/bin/phpunit -c phpunit.xml.dist --testsuite custom` passes
- [ ] From `web/themes/custom/pimlico`: `pnpm test`, `npx eslint .` and `npx prettier --check "components/**/*.{js,scss}" "src/**/*.{js,scss}"` pass
- [ ] Page and CSS comparisons against the baseline show only the changes you intended
- [ ] Docs that describe what you changed are updated
- [ ] Your report lists intended output changes, how each change was checked, what you left alone and why, and anything that also needs fixing in the shared skeleton

## Local gotchas

- **PHP version.** `valet isolate` sets the web server's PHP only. `vendor/bin/drush` and `php` use whichever PHP is first on `PATH`; it must be 8.4.
- **HTTP, not HTTPS.** `https://pimlico.test` falls through to Valet's default PHP until `valet secure pimlico` has been run. Use `http://pimlico.test` in scripts.
- **No builds.** The Vite dev server is always running; never run `pnpm build` or `pnpm dev`.
- **Front page.** `config/sync/system.site.yml` sets the front page to a staging node ID, so `drush cim` points the local front page at a missing node. Override it in `settings.local.php` (see the local set-up guide).
- **Memory.** `vendor/bin/drush` is a shell script in Drush 13; for more memory run `php -d memory_limit=-1 vendor/drush/drush/drush.php …`.
- **Caching.** If pages come back `X-Drupal-Cache: UNCACHEABLE (response policy)`, something called the page cache kill switch. Locally, any PHP warning shown on screen does this (verbose error display adds a message, and messages disable caching). Check `drush watchdog:show` for a warning on every request.
- **Random output.** "Similar brands" and the search page's suggestions are random; `compare-pages.sh` masks story cards for this reason. Compare those blocks by structure, not content.
