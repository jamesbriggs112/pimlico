# Pimlico Road: build plan

This is the delivery plan for the architecture in [TAD.md](TAD.md). Phases run in order. Within a phase, the numbered steps are the unit of work: each one ends in a PR from a `feature/…` branch into `dev`, with config exported. `dev` is promoted to `staging` and then `production` by PR.

**Working rules for every step** (from `AGENTS.md` and the reference project):

- Run `drush cim` after config changes and `drush cr` after significant ones. Export with `drush cex` and commit the YAML with the code that needs it.
- Scaffold components with `drush generate four-sdc`. Check whether a component exists before adding markup.
- Inspect the rendered output and its configuration before rewriting a template.
- Don't modify contrib modules. Put patches in `patches/`.
- `pnpm dev` is assumed to be running in the theme. Never run `pnpm build` locally to check work.
- Verify each step in the browser at desktop (1440) and mobile (390) against the Figma frame, and run the Storybook a11y check for any new component.

---

## Phase 0: Project set-up

| # | Step | Done when |
|---|---|---|
| 0.1 | Repository `jamesbriggs112/pimlico` created from the skeleton, with the skeleton as `upstream` and `dev`, `staging` and `production` branches (TAD §3). Done 11 November 2026. Still to do on GitHub: make `dev` the default branch if it isn't, protect `staging` and `production` (PRs only), and create the `staging` and `production` environments with their variables. | Branch protection and environments set |
| 0.2 | ✅ Local install: `composer install`, `settings.local.php`, `drush si --existing-config && drush ib`, Served at `http://localhost:8888` by `scripts/serve.sh`, because Valet needs admin rights this Mac doesn't have; move to Valet later (`docs/local-development.md`). The admin account has a random password; sign in with `drush uli`. | The site installs from config |
| 0.3 | ✅ Update `AGENTS.md` (dev URL, project name), add `CONTEXT.md` from TAD §5, `docs/agents/{code-review,domain}.md` copied from the reference project, and ADRs 0001–0006 (TAD §12). | Docs reviewed by the lead developer |
| 0.4 | ✅ Generate the theme: `php web/core/scripts/drupal generate-theme --starterkit four_starterkit pimlico --path themes/custom`. Set as default, `pnpm i`, `pnpm dev`. Bring across the reference project's theme additions the starterkit lacks: `init/_utilities`, `init/_animations`, `vitest` set-up and `tests/` folders. | The theme renders the skeleton content and Storybook runs |
| 0.5 | ✅ Composer additions: `drupal/address`, `drupal/office_hours`, `drupal/migrate_plus`, `drupal/migrate_tools`, `drupal/simple_sitemap`, and `drupal/search_api_db` (if not bundled). No cookie consent module: CookieScript is a hosted script. | `composer.lock` committed, modules enabled and exported |
| 0.6 | CI: set `THEME_NAME=pimlico` and the AWS variables (GitHub settings, still to do). ✅ Added a PHP job (`.github/workflows/checks.yml`) to the workflow for phpcs (`Drupal,DrupalPractice`) and PHPUnit on `web/modules/custom/pimlico_*`. | A PR runs lint and tests |

## Phase 1: Foundations

| # | Step | Done when |
|---|---|---|
| 1.1 | **Tokens and typography.** `_root.scss` from TAD §7.3, Lato and Fraunces (variable, OFL) self-hosted in `assets/fonts`, the type scale with `fluid()`, eyebrow and label styles, `contentWithGutters()` set to the 1280 / 80 grid. | A Storybook "Tokens" page matches the Figma styles |
| 1.2 | **Primitives:** `logo`, `icon` (SVG sprite), `button` (Dark, Yellow), `text_link`, `visually_hidden`, `button_icon` (media control). Every state from Figma, including Focus. | Stories for each variant, a11y addon clean |
| 1.3 | **Form controls:** `field` (input, select, textarea with error and focus), checkbox, `form` styling for Webform and core forms. | Contact form error state matches T15 · Form error |
| 1.4 | **Config clean-up:** remove unused skeleton paragraph types, vocabularies and views (TAD §6.3). Add `brand_category` and `area` vocabularies with terms. Add the image style sets and responsive image styles (TAD §7.4). | `drush cex` diff reviewed; nothing in the UI that the design can't render |

## Phase 2: Global layout

| # | Step | Done when |
|---|---|---|
| 2.1 | **Header:** desktop header with left and right menus, centred logo and search icon; transparent-over-hero variant for Home and Newson's Yard; sticky behaviour. | Matches Header (desktop and mobile) |
| 2.2 | **Mega menu:** four panels (Brands, Newson's Yard, News, Visit), driven by the `main` menu plus a featured-brands view block. Keyboard: open on Enter or Space, close on Escape, focus returns to the trigger. | Matches Mega menu frames; keyboard tested |
| 2.3 | **Mobile menu:** `<dialog>` overlay with `mobile_nav_item` accordions. | Matches Overlay · Mobile menu |
| 2.4 | **Footer and newsletter:** `footer`, `footer-information`, `social-media` and `legal` menus; Grosvenor logo; `newsletter` block (the form is built but not wired to a provider yet; that stays an open task, TAD §11). Export menus with `structure_sync`. | Matches Footer and Newsletter at both breakpoints |
| 2.5 | **Page title:** restyle `page_banner` with breadcrumbs (Easy Breadcrumb). | Matches Section · Page title |

## Phase 3: Section components and paragraphs

Build each SDC, then its paragraph type and the thin `paragraph--*.html.twig` adapter. Order follows reuse.

| # | Step | Templates unblocked |
|---|---|---|
| 3.1 | `section_heading`, `introduction`, `rich_text`, `quote` (restyle) | Most pages |
| 3.2 | `story_card`, `story_row`, `brand_card`, `content_grid` (restyle, with viewfield) | Home, News, related content |
| 3.3 | `hero_film` (video with poster, pause control, reduced-motion handling) | Home, Newson's Yard |
| 3.4 | `hero_split`, `feature_banner` (full-bleed and split styles), `visit_banner` | Brand, About, Newson's Yard, Contact |
| 3.5 | `image_gallery` (desktop row layouts; embla swipe gallery with arrows on mobile) | Brand, articles |
| 3.6 | `info_rows` / `info_row` | Visit, Brand specialisms, Contact enquiry routes |
| 3.7 | `brand_list` (hover image list) | Newson's Yard |
| 3.8 | `people`, `cta` (restyle), `webform` paragraph | About, Contact |
| 3.9 | `filter_link`, `pagination` | Listings |
| 3.10 | `audio_player` (plyr), `episode_row`, `media_video` (restyle) | Podcast, articles |

## Phase 4: Content types and listings

| # | Step | Done when |
|---|---|---|
| 4.1 | **Brand:** bundle and fields (TAD §6.1), form display with field groups (Content, Showroom, Map, SEO), Pathauto pattern, full view display, `node--brand--full.html.twig` composing hero, about, quote, gallery, specialisms, showroom details and similar brands. The map slot is left empty until 5.4. | T03 matches without the map |
| 4.2 | **Brands listing:** `brands` view with `page_brands` and `page_dine`, category filter as `filter_link`s, pagination, promo interstitial after row 2. Similar-brands block with category fallback (in `pimlico_brands` only if views can't do it). | T02 and T02b match |
| 4.3 | **News bundles:** `article`, `podcast_episode`, `press_article` with shared and specific fields, Pathauto, full view templates. | T08, T10, T12 and the Publication variant match |
| 4.4 | **News listing:** `news` view with featured story, grid, pagination; `block_latest`; `block_related`. | T07 matches; Home "This week" works |
| 4.5 | **Search:** Search API database index of Brands, Stories and Pages; `search` view; header search icon links to it. | T17 and T18 match |

## Phase 5: The map (`pimlico_map`)

The map project's `docs/PIMLICO-MAP-GUIDE.md` Part B is the spec. Its code samples are rewritten to project conventions (TAD §8): strict types, final classes, injected services, OOP hooks, attributes.

| # | Step | Done when |
|---|---|---|
| 5.1 | Module skeleton, `pimlico_unit` content entity with admin list and form, `field_address`, `field_map_buildings`, `field_area`. Library with the copied `pimlico-map.js` and `pimlico-map.json`. | Units can be created by editors |
| 5.2 | `BrandFeedController` at `/api/map/brands` returning the guide's B3 shape as a `CacheableJsonResponse`. Kernel test for shape, active-only filtering, Display on map, and cache tags. | Test passes; the feed validates against `output/map/brands.json` |
| 5.3 | `street_map` SDC wrapping `<pimlico-map>`, with the `filter_link` category bar and the filter behaviour (`Drupal.behaviors.pimlicoMapFilter`). `street_map` paragraph for the Visit page. | T13 "The street" works: filter, search, full screen, keyboard |
| 5.4 | Brand page map: pseudo-field through OOP `entity_extra_field_info` and `node_view` hooks, placed in the Brand view display beside the showroom details. | 03 Brand with Map matches at both breakpoints |
| 5.5 | Map picker widget for `field_map_buildings` (`pick` mode, read-only input, `Drupal.behaviors.pimlicoMapPicker`). | An editor can move a Unit by clicking a building |
| 5.6 | Run the map project's `npm test` suite against the Drupal pages, adjusting URLs. | All checks pass, or failures are logged as issues |

## Phase 6: Pages and content

| # | Step | Done when |
|---|---|---|
| 6.1 | **Migration:** `pimlico_migrate` with migrate_plus JSON sources creating Units then Brands from `map-source.json`. Fix the flagged placements with the picker. | 47 Brands and their Units imported; map matches the standalone demo |
| 6.2 | Build Home, Newson's Yard, Visit, About, Contact (with Webform and success page) and the three policy pages from paragraphs. | Each matches its Figma template at 1440 and 390 |
| 6.3 | Metatag defaults, Schema Metatag per bundle, Simple XML sitemap, robots.txt, redirects from belgraviavillage.com Pimlico URLs if required. | SEO checklist complete |
| 6.4 | CookieScript and Google Tag with Consent Mode v2: script library loaded first in `<head>`, script ID per environment, banner styled in the CookieScript dashboard, footer Cookie preferences link reopens it. Waits on the CookieScript account. | No tags fire before consent |

## Phase 7: Quality and launch

| # | Step | Done when |
|---|---|---|
| 7.1 | Accessibility audit: axe on every template, keyboard-only pass, VoiceOver on macOS and iOS, reduced motion, 200% zoom. | WCAG 2.2 AA issues fixed or logged with the client |
| 7.2 | Browser and device testing: Chrome, Firefox, Safari, iOS Safari (map pinch and full-screen overlay especially), Android Chrome. | Test log complete |
| 7.3 | Performance: Lighthouse on Home, Brand and Visit; image and video budgets; CloudFront caching and invalidation. | LCP under 2.5 s on 4G for Home and Brand |
| 7.4 | Editor training material and a short editorial guide (Brands, Units, map picker, News). | Handover session done |
| 7.5 | Staging sign-off, production deploy, DNS, Sentry environments, uptime monitoring. Add the real domains to `trusted_host_patterns` in the staging and production config (the skeleton only trusts `*.preflight.site` and `10.0.x.x`). | Live |

## Dependencies on the client

These block specific steps; everything else can proceed.

| Question (TAD §11) | Blocks |
|---|---|
| Newsletter provider | 2.4 wiring (open task) |
| CookieScript account and script ID | 6.4 |
| Map data questions (deferred) | 6.1 sign-off; the migration runs with current placements |
| Hero film footage | 3.3 final assets |
| Content migration beyond brands | 6.2 |
