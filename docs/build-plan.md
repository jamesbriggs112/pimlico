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
| 0.2 | ✅ Local install: `composer install`, `settings.local.php`, `drush si --existing-config && drush ib`, Served by Valet at `https://pimlico.test` (see `docs/local-development.md`; `scripts/serve.sh` is a fallback without admin rights). The admin account has a random password; sign in with `drush uli`. | The site installs from config |
| 0.3 | ✅ Update `AGENTS.md` (dev URL, project name), add `CONTEXT.md` from TAD §5, `docs/agents/{code-review,domain}.md` copied from the reference project, and ADRs 0001–0006 (TAD §12). | Docs reviewed by the lead developer |
| 0.4 | ✅ Generate the theme: `php web/core/scripts/drupal generate-theme --starterkit four_starterkit pimlico --path themes/custom`. Set as default, `pnpm i`, `pnpm dev`. Bring across the reference project's theme additions the starterkit lacks: `init/_utilities`, `init/_animations`, `vitest` set-up and `tests/` folders. | The theme renders the skeleton content and Storybook runs |
| 0.5 | ✅ Composer additions: `drupal/address`, `drupal/office_hours`, `drupal/migrate_plus`, `drupal/migrate_tools`, `drupal/simple_sitemap`, and `drupal/search_api_db` (if not bundled). No cookie consent module: CookieScript is a hosted script. | `composer.lock` committed, modules enabled and exported |
| 0.6 | CI: set `THEME_NAME=pimlico` and the AWS variables (GitHub settings, still to do). ✅ Added a PHP job (`.github/workflows/checks.yml`) to the workflow for phpcs (`Drupal,DrupalPractice`) and PHPUnit on `web/modules/custom/pimlico_*`. | A PR runs lint and tests |

## Phase 1: Foundations

| # | Step | Done when |
|---|---|---|
| 1.1 | ✅ **Tokens and typography.** `_root.scss` from TAD §7.3, Lato and Fraunces (variable, OFL) self-hosted in `assets/fonts`, the type scale with `fluid()`, eyebrow and label styles, `contentWithGutters()` set to the 1280 / 80 grid. | A Storybook "Tokens" page matches the Figma styles |
| 1.2 | ✅ **Primitives:** `logo`, `icon` (SVG sprite), `button` (Dark, Yellow), `text_link`, `visually_hidden`, `button_icon` (media control). Every state from Figma, including Focus. | Stories for each variant, a11y addon clean |
| 1.3 | ✅ **Form controls:** `field` (input, select, textarea with error and focus), checkbox, `form` styling for Webform and core forms. | Contact form error state matches T15 · Form error |
| 1.4 | ✅ **Config clean-up:** remove unused skeleton paragraph types, vocabularies and views (TAD §6.3). Add `brand_category` and `area` vocabularies with terms. Add the image style sets and responsive image styles (TAD §7.4). | `drush cex` diff reviewed; nothing in the UI that the design can't render |

**Phase 1 notes (11 November 2026):**

- Lato comes from Lato 2.0 (npm `lato-font`), subset to Latin, because Google's Lato has no Medium (500). Sources are in `assets/fonts/README.md`.
- **Design query, button hover:** Figma draws the hover as an outline, but its description says "mustard fill + dark label (4.8:1)" and `--color-action-primary-hover` is the same yellow as the default. The build follows the description, with `--color-brand-mustard: #dab845` (4.8:1 against dark grey). Confirm the colour with design.
- **Design query, caps text link:** the underlined and hover caps variants are 12px in Figma; the rest-state caps link is 14px. The build keeps the size steady on hover (no jump) and uses 12px only for "underlined at rest".
- The 4:3 image styles (`l23_*_webp`, `aspect_ratio_4_3`, media view mode `4_3`) are copied from the reference project. The 4:5 set still needs a ratio code agreed with the lead developer (TAD §7.4).
- Brand category and Area terms are exported with Structure Sync (`drush et`); import them elsewhere with `drush it` (safe mode).

## Phase 2: Global layout

| # | Step | Done when |
|---|---|---|
| 2.1 | ✅ **Header:** desktop header with left and right menus, centred logo and search icon; transparent-over-hero variant for Home and Newson's Yard; sticky behaviour. | Matches Header (desktop and mobile) |
| 2.2 | ✅ **Mega menu:** four panels (Brands, Newson's Yard, News, Visit), driven by the `main` menu plus a featured-brands view block. Keyboard: open on Enter or Space, close on Escape, focus returns to the trigger. | Matches Mega menu frames; keyboard tested |
| 2.3 | ✅ **Mobile menu:** `<dialog>` overlay with `mobile_nav_item` accordions. | Matches Overlay · Mobile menu |
| 2.4 | ✅ **Footer and newsletter:** `footer`, `footer-information`, `social-media` and `legal` menus; Grosvenor logo; `newsletter` block (the form is built but not wired to a provider yet; that stays an open task, TAD §11). Export menus with `structure_sync`. | Matches Footer and Newsletter at both breakpoints |
| 2.5 | ✅ **Page title:** restyle `page_banner` with breadcrumbs (Easy Breadcrumb). | Matches Section · Page title |

**Phase 2 notes (11 November 2026):**

- The mega menu uses **Advanced Mega Menu**, as in the reference project. Each top-level item's children are the panel's primary links (and the mobile accordion). The "Featured"/"Latest" list and the feature card are added per item under Structure » Menus » Main navigation once the brands and news views exist (4.2, 4.4). Panel layouts are editor-managed, so `advanced_mega_menu.megamenu_content.*` is in Config Ignore, as in the reference project.
- A menu link's description is its panel eyebrow (Brands: "Explore the street").
- The newsletter is the `newsletter` Webform in a new Newsletter region. It saves nothing and posts nowhere until a provider is chosen. `~webform.webform.newsletter` is excluded from Config Ignore so it deploys with the site.
- The Figma "Page title" is a new `page_title` component; the starterkit `page_banner` is an image banner and stays for that. Breadcrumbs render inside it via `drupal_block('system_breadcrumb_block')`, not as a page region.
- The overlay header (transparent over a hero) is built as `header--overlay`; pages switch it on with the `header_overlay` variable in 3.3.
- Menu links are exported with Structure Sync (`drush em`); the copyright block with `drush eb`.
- Removed the starterkit's unused hamburger, header and footer partials.

## Phase 3: Section components and paragraphs

Build each SDC, then its paragraph type and the thin `paragraph--*.html.twig` adapter. Order follows reuse.

| # | Step | Templates unblocked |
|---|---|---|
| 3.1 | ✅ `section_heading`, `introduction`, `rich_text`, `quote` (restyle) | Most pages |
| 3.2 | ✅ `story_card`, `story_row`, `brand_card`, `content_grid` (restyle, with viewfield) | Home, News, related content |
| 3.3 | ✅ `hero_film` (video with poster, pause control, reduced-motion handling) | Home, Newson's Yard |
| 3.4 | ✅ `hero_split`, `feature_banner` (full-bleed and split styles), `visit_banner` | Brand, About, Newson's Yard, Contact |
| 3.5 | ✅ `image_gallery` (desktop row layouts; embla swipe gallery with arrows on mobile) | Brand, articles |
| 3.6 | ✅ `info_rows` / `info_row` | Visit, Brand specialisms, Contact enquiry routes |
| 3.7 | ✅ `brand_list` (hover image list) | Newson's Yard |
| 3.8 | ✅ `people`, `cta` (restyle), `webform` paragraph | About, Contact |
| 3.9 | ✅ `filter_link`, `pagination` | Listings |
| 3.10 | ✅ `audio_player` (plyr), `episode_row`, `media_video` (restyle) | Podcast, articles |

**Phase 3 notes (11 November 2026):**

- **Section components:** `section_heading`, `introduction`, `rich_text`, `quote`, `story_card`, `story_row`, `card_grid`, `hero_film`, `hero_split`, `feature_banner`, `visit_banner`, `image_gallery`, `info_row`/`info_rows`, `brand_list`, `next_step`, `form_section`, `filter_bar`, `pagination`, `media_video`, `audio_player`, `episode_row`. Measured against Figma at 1440 and 390.
- **Content model changes from the Figma detail:**
  - About "People" turned out to be two story cards, so it's the Cards paragraph (`content_grid`) with hand-picked items; there are no people/person types.
  - "Next step" is the skeleton `cta`, now with eyebrow, heading, text and up to three buttons (`field_links`).
  - Introduction covers the Home collage and the centred Newson’s Yard layout with an optional film; About is an Image section plus a Rich text section.
- **Pages** render as full-width section stacks (`node--page--full`). They get the Page title band (title plus a new `field_summary` intro) unless the first section is a hero; a film hero switches the header to its transparent version.
- **Images** use the uncropped `full` media view mode and are cropped to each slot by CSS. Switch cards to focal-point crops once the 4:5 image style code is agreed.
- **Hero films** play through a new media `background` view mode (autoplay, muted, loop, inline, no controls), with a pause control and a poster for reduced motion.
- **Forms:** Inline Form Errors is enabled so errors sit under each field, as in Figma. Status messages are restyled as an in-page band (Figma has no design for them). The `contact` webform is added and deployed with config (`~webform.webform.contact`); who receives its emails is an open question.
- **Not yet wired:** the brand list, filter bar, story row, audio player and episode row are built with Storybook stories; their paragraphs or views arrive with Brands, News and podcasts in phase 4.
- **Design queries:** the Yard banner lock-up is white everywhere (Figma uses yellow on About); filter links stay 14px in every state (Figma draws 12px when active); story card hover zooms the image without Figma's 88% fade.
- **QA pages:** "Component test" pages (unpublished, local only) exercise every section.

## Phase 4: Content types and listings

| # | Step | Done when |
|---|---|---|
| 4.1 | **Brand:** bundle and fields (TAD §6.1), form display with field groups (Content, Showroom, Map, SEO), Pathauto pattern, full view display, `node--brand--full.html.twig` composing hero, about, quote, gallery, specialisms, showroom details and similar brands. The map slot is left empty until 5.4. | T03 matches without the map |
| 4.2 | **Brands listing:** `brands` view with `page_brands` and `page_dine`, category filter as `filter_link`s, pagination, promo interstitial after row 2. Similar-brands block with category fallback (in `pimlico_brands` only if views can't do it). | T02 and T02b match |
| 4.3 | **News bundles:** `article`, `podcast_episode`, `press_article` with shared and specific fields, Pathauto, full view templates. | T08, T10, T12 and the Publication variant match |
| 4.4 | **News listing:** `news` view with featured story, grid, pagination; `block_latest`; `block_related`. | T07 matches; Home "This week" works |
| 4.5 | **Search:** Search API database index of Brands, Stories and Pages; `search` view; header search icon links to it. | T17 and T18 match |

**Phase 4 notes (built):**

- **Brand page:** hero, about, quote, gallery, specialisms, a "Find the showroom" band (address, opening hours, trade, contact, accessibility, directions to Google Maps, website) and Similar Brands (chosen, or three random from the same category). The map slot waits for phase 5.
- **Listings:** `/brands` (category filter, promo band after the sixth card, 15 per page), `/dine` (Food & Drink and its child categories: Pubs and restaurants, Cafés) and `/news` (featured story, News/Press filter, 12 per page). The filter bars and the promo position come from `includes/views.inc`; Views can't build them.
- **Stories:** article, podcast episode and press article templates follow T08, T10 and T12. Image sections inside stories render as the 760px `figure`. Related stories fall back to the same News Category.
- **Featured story:** `field_featured` defaults to off; the News grid excludes the featured story, which the attachment shows above it.
- **Search:** `/search` indexes Brands and Stories (pages are left out; they are few and reached from the menu). Results are grouped Brands, Dine, News, Press with counts in the filter bar, three top hits per group, and every result with a pager once a group is chosen. The `pimlico_search` module's processor assigns each node its group (kernel test included).
- **Content model changes:** opening hours are text lines, not office_hours. Brand and story images are `field_image` (was `field_banner`); intros are `field_summary` (was `field_teaser`). Article `body`, `field_banner` and `field_teaser` were removed.
- **Sample content:** 47 brands from the map project's import and 11 placeholder stories, local only. Phase 6 replaces them with the migration and real copy.
- **Design queries:**
  - "Popular searches" is a fixed list in `includes/search.inc` (Antiques, Interiors, Lighting, Lunch, Newson’s Yard, Art). Should editors manage it?
  - The no-results state isn't designed. It currently shows "No results…" with the empty text.
  - T18 draws only Brands and Stories groups. The build shows a group for every type with results (Brands, Dine, News, Press).
  - Related-section headings follow each Figma page ("Also on this street", "Continue the conversation", "Discover the places in the story"); confirm whether they should be editable.

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

**Phase 5 notes (built):**

- **`pimlico_map` module:**
  - The `pimlico_unit` entity (base fields; admin list under Content » Units).
  - `field_units` on Brand.
  - The map component and base map copied unchanged from the map project at version 1.0.0 (`assets/`). The library version is also the base map's `?v=`.
- **Tenancy Feed** at `/api/map/brands`: the B3 shape, cached by tag (`node_list:brand`, `pimlico_unit_list`, `taxonomy_term_list:brand_category`). CloudFront is purged by URL when a Brand or Unit changes. Sub-categories roll up to their top-level category (Cafés → Food & Drink), so the map's filter only needs top-level terms. A Brand's map ID is the last segment of its path alias. Kernel tests cover the shape, the filtering and the cache tags.
- **Visit page:** a new `street_map` paragraph (eyebrow, heading) renders the `street_map` component: heading, category filter buttons and the map with search and locate. It's offered on pages only.
- **Brand page:** a "Showroom map" pseudo-field (OOP hooks) fills the media half of "Find the showroom" when the Brand is active, shown on the map and placed. Otherwise the band has no media column.
- **Picker:** Units use the "Map building picker" widget. It shows the map once in pick mode above read-only inputs; a click fills the box the editor last selected, else the first empty one.
- **QA:** `scripts/map-qa.sh` runs the map project's 96-check browser suite against the Drupal pages. All 96 pass locally, including the strict CSP check. Only one check needed changing: Drupal never renders a map for an unknown brand, so that check switches the Visit map to an unknown brand instead of loading a page.
- **Sample data:** Units for the 47 brands come from the map project's placements (local only). Phase 6's migration creates them properly, and the flagged placements (map guide B7) still need checking with the picker.
- **Not done:** Safari/iOS device testing and a screen-reader pass (phase 7). The skeleton's old `map` paragraph is no longer offered on pages; its config is left in place until someone decides to delete it.

## Phase 6: Pages and content

| # | Step | Done when |
|---|---|---|
| 6.1 | **Migration:** `pimlico_migrate` with migrate_plus JSON sources creating Units then Brands from `map-source.json`. Fix the flagged placements with the picker. | 47 Brands and their Units imported; map matches the standalone demo |
| 6.2 | Build Home, Newson's Yard, Visit, About, Contact (with Webform and success page) and the three policy pages from paragraphs. | Each matches its Figma template at 1440 and 390 |
| 6.3 | Metatag defaults, Schema Metatag per bundle, Simple XML sitemap, robots.txt, redirects from belgraviavillage.com Pimlico URLs if required. | SEO checklist complete |
| 6.4 | CookieScript and Google Tag with Consent Mode v2: script library loaded first in `<head>`, script ID per environment, banner styled in the CookieScript dashboard, footer Cookie preferences link reopens it. Waits on the CookieScript account. | No tags fire before consent |

**Phase 6 notes (built, 11 November 2026):**

- **Migration (6.1):** `pimlico_migrate` imports Units then Brands from `data/brands.json`, which `scripts/build-source.php` builds from the map project's data. It splits each free-text address into parts, Unit name and Area, and corrects the source's one "SW1 8LP" postcode. `drush migrate:import --tag=pimlico` creates all 47, each on the same Map Buildings as the map project's demo, and `scripts/map-qa.sh` still passes 96/96. Brand categories come in at the top level, so editors move cafés and restaurants into the Food & Drink sub-categories.
- **Pages (6.2):** `scripts/dev/seed-pages.php` builds Home (set as the front page), Newson's Yard, Visit, About, Contact, Privacy, Cookies and Accessibility from the Figma copy, using placeholder text where Figma has it. The script can be re-run. New sections:
  - **Info band:** Visit "Location & directions", with an inset photo that sits above the text on mobile.
  - **Policy text:** the contents list is built from the Heading 2s; the "Last updated" date is the page's last change.
  - **Split text:** About "A street with a story".
  - A **Background** option (white or linen) on Cards and Info rows.
  - A Next step with only a heading and buttons becomes the compact "Related information" bar.
  - The Image section's "Full width" layout gives the 2:1 wide image (About).
- **SEO (6.3):**
  - Meta tag fallbacks now use `field_summary` and `field_image`.
  - Pages and Brands get WebPage schema and stories get Article schema (NewsArticle for press).
  - The sitemap covers pages, Brands and Stories, plus /brands, /dine and /news.
  - **Deploy:** set `$config['simple_sitemap.settings']['base_url']` per environment in `settings.php`, or sitemaps generated by `drush cron` list `http://default`.
  - **Not done:** redirects from belgraviavillage.com, pending the list of old URLs.
- **CookieScript (6.4):** not started; it waits on the CookieScript account.
- **Front page:** `system.site` stores the Home page's node ID, which differs between environments. Run `seed-pages.php` on staging (it sets the front page), or set it by hand after creating Home.
- **Design queries:**
  - The latest desktop Visit design (03 Brand with Map page) drops "Location & directions" and "Plan around the street", but the mobile design keeps "Location & directions" and its destination links. The build keeps "Location & directions" at both sizes and leaves out "Plan around the street" and the destination links.
  - Contact's "While you are here" button pointed at events, which are out of scope; it now says "Explore the brands".
  - Six brands have no postcode in the source (Roseberys, Lapicida, The New Craftmaker, OCHRE, Volga Linen, CTO Lighting); editors fill them in.

## Phase 7: Quality and launch

| # | Step | Done when |
|---|---|---|
| 7.1 | Accessibility audit: axe on every template, keyboard-only pass, VoiceOver on macOS and iOS, reduced motion, 200% zoom. | WCAG 2.2 AA issues fixed or logged with the client |
| 7.2 | Browser and device testing: Chrome, Firefox, Safari, iOS Safari (map pinch and full-screen overlay especially), Android Chrome. | Test log complete |
| 7.3 | Performance: Lighthouse on Home, Brand and Visit; image and video budgets; CloudFront caching and invalidation. | LCP under 2.5 s on 4G for Home and Brand |
| 7.4 | Editor training material and a short editorial guide (Brands, Units, map picker, News). | Handover session done |
| 7.5 | Staging sign-off, production deploy, DNS, Sentry environments, uptime monitoring. Add the real domains to `trusted_host_patterns` in the staging and production config (the skeleton only trusts `*.preflight.site` and `10.0.x.x`). | Live |

**Phase 7 notes (11 November 2026):** see `docs/launch-checklist.md` for results and what is left, and `docs/editor-guide.md` for 7.4.

- **Security:** Drupal core 11.4.12 (from 11.4.7, including a critical remote code execution fix) and patched contrib and Symfony packages. `composer audit` is clean.
- **Accessibility:** axe shows no WCAG 2.2 AA violations on 17 templates at 1440 and 390. I fixed text contrast (a darker pumpkin for text), eyebrow colours on dark bands, info row `<dl>` markup and swipe-track overflow. Keyboard focus, 200% zoom and reduced motion all check out. Screen reader and device testing are logged as manual.
- **Performance:** above-the-fold images load eagerly (the new "Hero" media view mode). The hero film no longer downloads before the page (`preload="none"`, starts after load, skipped on data saver or slow connections). Mobile LCP on slow 4G: Home 2.6 s, Brand 0.9 s, Visit 0.7 s, News 0.8 s.
- **Deploy:** the post-deploy hook now runs `drush deploy`, so config is imported. `SITE_HOST` and `HASH_SALT` come from environment properties.
- **CMS branding:** Gin uses the Pimlico navy accent and a "P" monogram, the login page shows the wordmark and a Newson's Yard photo, and `pimlico_admin` gives admin page titles Fraunces. The site favicon is the monogram.

## Dependencies on the client

These block specific steps; everything else can proceed.

| Question (TAD §11) | Blocks |
|---|---|
| Newsletter provider | 2.4 wiring (open task) |
| CookieScript account and script ID | 6.4 |
| Map data questions (deferred) | 6.1 sign-off; the migration runs with current placements |
| Hero film footage | 3.3 final assets |
| Content migration beyond brands | 6.2 |
