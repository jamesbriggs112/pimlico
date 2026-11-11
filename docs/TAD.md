# Pimlico Road: Technical Architecture Document

| | |
|---|---|
| **Project** | The Pimlico Road website (Grosvenor, part of Belgravia) |
| **Design source** | Figma "Pimlico Visuals – V01" (`REw9zZENN4OEozQHrt17pD`): pages 01 Desktop, 02 Mobile, 03 Brand with Map, Components |
| **Starting point** | `four-drupal-skeleton` (Drupal 11, `four_starterkit` theme, Vite, SDC, Storybook) |
| **Conventions source** | `Reference/sheffield-chamber-of-commerce` (lead developer's project) |
| **Map source** | `~/Documents/Builds/pimlico-map` (`<pimlico-map>` web component and data pipeline) |
| **Status** | Draft for review, 11 November 2026 |

The build plan that goes with this document is in [build-plan.md](build-plan.md).

---

## 1. Summary

Pimlico Road is a brochure and discovery site for one London street: its brands (showrooms, dealers, restaurants), Newson's Yard, news, and visitor information. Its distinctive feature is an interactive bird's-eye map of the street, which already exists as a standalone, tested web component.

The site is a standard Four Drupal build:

- **Drupal 11** from `four-drupal-skeleton`, hosted on the Four AWS Elastic Beanstalk stack.
- **One custom theme, `pimlico`**, generated from `four_starterkit`, with every visual pattern built as a Single Directory Component (SDC) and documented in Storybook.
- **Paragraph-based pages** for editorial flexibility, plus three structured content types (Brand, News, and a Unit entity for the map).
- **Client-specific modules prefixed `pimlico_`**, following the reference project's PHP and hook conventions.
- **The map integrated as `pimlico_map`**, a module that serves the tenancy feed from Drupal and renders the existing component unchanged.

There is no commerce, membership or CRM integration, so the architecture is much smaller than the reference project. The conventions still apply in full.

## 2. Scope

### 2.1 Templates in the design

| Ref | Template | Drupal implementation |
|---|---|---|
| T01 | Home | `page` node with `field_sections` |
| T02 | Brands (listing, category filter, pagination, promo interstitial) | `brands` view, page display |
| T02b | Dine (Food & Drink listing) | `brands` view, display pre-filtered to Food & Drink |
| T03 | Brand detail, with "Find the showroom" map | `brand` node, full view mode |
| T06 | Newson's Yard (film hero, resident brands, intro, visit banner) | `page` node with `field_sections` |
| T07 | News (featured story, story grid, pagination) | `news` view, page display |
| T08 | News article | `article` node |
| T10 | Podcast episode (audio player, notes, transcript) | `podcast_episode` node |
| T12 | Press article (publication, source), plus the "Publication" variant | `press_article` node |
| T13 | Visit (page title, "The street" overview map, location, getting here) | `page` node; the map is a `street_map` paragraph |
| T14 | About Pimlico Road (intro, yard banner, area story, people, next step) | `page` node with `field_sections` |
| T15 | Contact (enquiry routes, form, error and success states, visit banner) | `page` node with a `webform` paragraph |
| T16 | Privacy, Cookies, Accessibility | `page` node with rich text |
| T17/T18 | Search, search results | Search API view |

Global elements: desktop header with mega menus (Brands, Newson's Yard, News, Visit), mobile menu overlay, newsletter section, footer (Explore, Information, Follow, legal links, Pimlico Road and Grosvenor logos), and a Cookie preferences link that reopens the CookieScript consent banner.

### 2.2 Out of scope

- Events. The Components page has an Event card and Event row, but events were confirmed as no longer required on 11 November 2026.
- Transactions, accounts or a members' area.
- Multilingual content. The site is English only.

## 3. Repository and environments

**The skeleton is an upstream source, not a project repository.** Pimlico Road lives in its own repository, `git@github.com:jamesbriggs112/pimlico.git`, cloned from the skeleton with its full history:

| Remote | Repository | Use |
|---|---|---|
| `origin` | `jamesbriggs112/pimlico` | The project |
| `upstream` | `Four-Agency/four-drupal-skeleton` | Skeleton updates: `git fetch upstream && git merge upstream/main` into `dev` |

**Branches:**

| Branch | Purpose | Deploys to |
|---|---|---|
| `dev` | Default branch. Feature branches (`feature/…`, `bugfix/…`) are merged here by PR. | Nothing (local development) |
| `staging` | Release candidates, merged from `dev` by PR | AWS EB staging, on a merged PR |
| `production` | Live code, merged from `staging` by PR | AWS EB production, on a merged PR |

| Environment | URL | Notes |
|---|---|---|
| Local | `http://localhost:8888` | PHP's built-in server (`scripts/serve.sh`) until Valet can be installed, then `https://pimlico.test` on Valet (not DDEV). See `docs/local-development.md`. PHP 8.4 (the Composer lock needs 8.4 or later; the AWS platform must match), Node 22, pnpm. `pnpm dev` runs in the theme. |
| Staging | AWS EB staging environment | `.github/workflows/deploy.yml`, on a PR merged into `staging` |
| Production | AWS EB production environment | `.github/workflows/deploy.yml`, on a PR merged into `production` |

The workflow picks the GitHub environment (`staging` or `production`) from the PR's base branch, so each environment's variables (`ENVIRONMENT_NAME`, `SENTRY_ENVIRONMENT`, and so on) are set on the matching GitHub environment.

Environment-specific settings follow the skeleton pattern: `config/{development,staging,production}/{env}.config.php`, loaded by `settings.php` from `$_SERVER['ENVIRONMENT']`. Secrets come from environment variables and are never exported to config. The project does not use config_split.

## 4. Naming conventions

These follow the reference project exactly. Where Pimlico needs a new name, it uses the same pattern.

| Thing | Convention | Pimlico examples |
|---|---|---|
| Theme | short client machine name | `pimlico` |
| Client modules | `pimlico_*`, `package: Pimlico` | `pimlico_map`, `pimlico_brands`, `pimlico_news`, `pimlico_migrate` |
| Reusable agency modules | `four_*` or unprefixed generic | `four_sdc`, `custom_image_styles` (from the skeleton) |
| Node bundles | snake_case singular nouns | `brand`, `article`, `podcast_episode`, `press_article`, `page` |
| Paragraph types | snake_case, same name as the SDC they render | `hero_film`, `hero_split`, `street_map`, `info_rows` |
| Vocabularies | `{context}_{type}` | `brand_category`, `news_category` |
| Fields | `field_` + snake_case, no bundle prefix, storage reused across bundles | `field_heading`, `field_text`, `field_media`, `field_link`, `field_sections`, `field_banner`, `field_teaser` |
| SEO fields | the shared set | `field_seo_title`, `field_seo_description`, `field_seo_image`, `field_seo_social_media_title` |
| Views | plural nouns | `brands`, `news`, `search` |
| Menus | kebab-case | `main`, `footer`, `legal`, `social-media`, `footer-information` |
| Blocks | `{theme}_{name}` | `pimlico_main_menu`, `pimlico_newsletter` |
| SDC folders | snake_case nouns | `story_card`, `mega_menu`, `info_row` |
| CSS classes | BEM, block = kebab-case of the component | `.story-card__image`, `.mega-menu__panel`, `.button--yellow` |
| CSS custom properties | brand, then semantic, then component | `--color-brand-yellow`, `--color-action-primary`, `--button-hover-background-color-yellow` |
| JS behaviours | camelCase in an IIFE with `once` and `Drupal` | `Drupal.behaviors.heroFilm` |
| Image styles | `{ratio}_{size×1000}_{W}x{H}` NIS sets | `l23_064000_1024x768` (see [§7.4](#74-images-and-media)) |
| ADRs | `docs/adr/NNNN-imperative-kebab-title.md`, prose, no status header | `0001-keep-the-map-component-as-an-unmodified-dependency.md` |

## 5. Domain language

This becomes `CONTEXT.md` in the project repository, in the reference project's format.

- **Brand:** a business trading on Pimlico Road or in Newson's Yard, with its own page. Restaurants and cafés are Brands in the Food & Drink category. _Avoid:_ shop, tenant, retailer.
- **Brand Category:** the single primary grouping a Brand is filtered by: Home & Interiors, Antiques and art, Soft furnishings and fabrics, Lighting, Food & Drink. _Avoid:_ tag, sector.
- **Specialism:** a short line describing what a Brand offers, shown in "What you'll find". _Avoid:_ service, category.
- **Unit:** a physical shopfront that a Brand trades from. A Unit points at one or more Map Buildings, and outlives the Brands that occupy it. _Avoid:_ address, location.
- **Map Building:** a building or shop-unit ID in the Base Map, such as `w535769837` or `w535769910-76-80`. Editors pick it on the map and never type it.
- **Base Map:** the static geometry file (`pimlico-map.json`) built from OpenStreetMap by developers.
- **Tenancy Feed:** the JSON list of active Brands and their Map Buildings that Drupal serves to the map.
- **Tenancy Status:** whether a Brand lights its Units on the map: `active`, `coming_soon` or `closed`.
- **Story:** any item in News: an Article, a Podcast Episode or a Press Article. _Avoid:_ post, blog.
- **Newson's Yard:** the redeveloped timber yard off Pimlico Road. It is an Area, and its Brands are Resident Brands.

## 6. Content architecture

### 6.1 Node types

**`brand`** (T03, cards in T02, T02b and mega menu)

| Field | Type | Notes |
|---|---|---|
| `title` | core | Brand name |
| `field_brand_category` | entity reference → `brand_category` (single) | Filter bar, eyebrow, map category |
| `field_teaser` | plain text, long | Hero intro and card text |
| `field_banner` | media reference → `image` | Shopfront hero (split hero, 680 × 850) |
| `field_heading` + `field_text` | string + formatted text | "About the house" |
| `field_quote` | paragraph reference → `quote` | Optional pull quote |
| `field_gallery` | media reference → `image` (multiple) | "Inside the showroom". Layout chosen by image orientation (§7.3). |
| `field_gallery_credit` | string | "Photography: …" |
| `field_specialisms` | paragraph reference → `info_row` (multiple) | "What you'll find" |
| `field_address` | address | Showroom address |
| `field_opening_hours` | office_hours | Structured, see open questions |
| `field_trade_info` | plain text | "Trade" row |
| `field_email`, `field_phone` | email, telephone | "Contact" row |
| `field_link` | link | Website button |
| `field_unit` | entity reference → `pimlico_unit` (multiple) | **The link to the map** |
| `field_tenancy_status` | list (`active`, `coming_soon`, `closed`) | Only `active` lights the map |
| `field_display_on_map` | boolean, default on | |
| `field_map_label` | string, max 24 | Shorter name for map labels |
| `field_similar_brands` | entity reference → `brand` (max 3) | Falls back to three Brands in the same category |
| SEO fields | shared set | |

The Brand's map ID is its path alias's last segment, generated by Pathauto (`brands/[node:title]`) and locked once published. A separate `field_map_id` is only needed if the client expects to rename brands; see open questions.

**`article`**, **`podcast_episode`**, **`press_article`** (News, T07–T12)

Three bundles rather than one with a type switch, because each has fields the others don't, and separate bundles keep the edit forms clean. ADR 0004 records this. They share these fields: `field_banner`, `field_teaser`, `field_news_category`, `field_sections` (body paragraphs), `field_related` (manual related content, falling back to the same category), and the SEO set. Bundle-specific fields:

- `podcast_episode`: `field_audio` (media `audio`), `field_episode_number`, `field_duration`, `field_transcript` (formatted text, shown in a `<details>`).
- `press_article`: `field_publication` (string), `field_publication_logo` (media), `field_source_link` (link), `field_publication_date`.

The existing `news_category` vocabulary in the skeleton is reused for the News filters.

**`page`** (Home, Newson's Yard, Visit, About, Contact, policies)

Existing skeleton bundle: `field_banner` (optional page title or hero), `field_sections` (paragraphs) and the SEO set.

### 6.2 Unit entity

**`pimlico_unit`** is a custom content entity defined in `pimlico_map`, with no public route. A Unit is an editor-only record, so an entity avoids public node pages, Pathauto, sitemap entries and SEO fields for something no visitor should see. Taxonomy was rejected because term pages are public by default and its admin UI makes a poor list of shopfronts. ADR 0003 records this.

| Field | Type | Notes |
|---|---|---|
| `label` | string | "95–97 Pimlico Road" |
| `field_address` | address | |
| `field_map_buildings` | string (multiple) | Set with the map picker widget |
| `field_area` | entity reference → `area` vocabulary | Pimlico Road, Newson's Yard, Ebury Street… |
| `field_entrance_note` | string | Optional |

### 6.3 Vocabularies

| Vocabulary | Status | Use |
|---|---|---|
| `brand_category` | new | Filter bars, map category, brand eyebrow. Weighted for filter order. |
| `news_category` | skeleton | News filters and eyebrows (Interiors, Food & Drink, …) |
| `area` | new | Unit grouping |
| `paragraph_styles`, `image_paragraph_styles` | skeleton | Paragraph style variants |

Unused skeleton vocabularies (`download_styles`, `tags`) and paragraph types (`document`, `document_list`, `download`, `iframe_embed`, `jump_menu_anchor`, `map_image`, `separator`) are removed in phase 1 so editors only see what the design supports.

### 6.4 Paragraph types

Each renders the SDC of the same name (§7.2). Skeleton types are kept where the design matches and restyled.

| Paragraph | Origin | Used in | Fields |
|---|---|---|---|
| `hero_film` | new | Home, Newson's Yard | `field_heading`, `field_media` (video), `field_media_poster` (image), `field_link` |
| `hero_split` | new | Brand (via node template), About | `field_heading`, `field_text`, `field_media`, `field_link` |
| `page_banner` | skeleton | Most pages ("Page title") | `field_heading`, `field_text`, breadcrumb from Easy Breadcrumb |
| `introduction` | new | Home, About, Newson's Yard | `field_eyebrow`, `field_heading`, `field_text`, `field_link`, `field_media` (up to 3, collage) |
| `content_grid` | skeleton | "This week on Pimlico Road", Related content, Similar brands | `field_eyebrow`, `field_heading`, `field_link`, `field_view` (viewfield) or manual references |
| `feature_banner` | new | Newson's Yard banner, Yard banner | `field_eyebrow`, `field_heading`, `field_text`, `field_link`, `field_media`; style: full-bleed or split |
| `visit_banner` | new | Plan your visit (Home, Contact, Newson's Yard) | `field_eyebrow`, `field_heading`, `field_text`, `field_link`, `field_media` |
| `quote` | skeleton | Brand, articles | `field_text`, `field_cite` |
| `image_gallery` | new | Brand, articles | `field_media` (multiple), `field_heading`, `field_caption` |
| `info_rows` / `info_row` | new | Getting here, Specialisms, Enquiry routes | `field_eyebrow`, `field_heading`, `field_text`; rows: `field_heading`, `field_text`, `field_link` |
| `street_map` | new | Visit | `field_eyebrow`, `field_heading`; renders the overview map and category filter |
| `brand_list` | new | Newson's Yard "Resident brands" (the A / Brand list hover component) | `field_heading`, references → `brand` |
| `people` | new | About "People" | `field_heading`; people as `person` paragraphs (name, role, photo) |
| `cta` | skeleton | About "Next step" | restyled |
| `text_wysiwyg_` | skeleton | Policies, article bodies | name kept for compatibility |
| `video` | skeleton | Articles (Media / Video) | restyled |
| `webform` | new | Contact | `field_webform` (webform reference) |

### 6.5 Views

| View | Displays |
|---|---|
| `brands` | `page_brands` (T02, `/brands`, exposed category filter as Filter links, 15 per page, promo interstitial after row 2); `page_dine` (`/dine`, Food & Drink); `block_similar` (same category, excludes current); `block_mega_menu` (featured brands) |
| `news` | `page_news` (T07, featured story plus grid, pagination); `block_latest` ("This week"); `block_related` |
| `search` | `page_search` (T17/T18), Search API with a database backend |

### 6.6 Menus

`main` (Brands, Dine, Newson's Yard, News, Visit, with mega menu children), `footer` (Explore), `footer-information` (Information), `social-media` (Follow), `legal` (Privacy, Cookies, Cookie preferences). Menu links are exported with `structure_sync`.

## 7. Theme architecture (`pimlico`)

### 7.1 Generation and structure

Generated with:

```bash
php web/core/scripts/drupal generate-theme --starterkit four_starterkit pimlico --path themes/custom
```

It keeps the reference project's layout: `components/`, `templates/{block,field,form,layout,media,menu,navigation,node,page,paragraph,region,views}`, `includes/*.inc` (one per entity or area, required by `pimlico.theme`), `src/{js,sass,stories}`, `tests/{js,src/Kernel}` and `assets/{fonts,icons}`.

Rules carried over from the reference project and `AGENTS.md`:

- Drupal templates are thin adapters. They map fields onto SDCs with `include`/`embed` using the `pimlico:` namespace and `only`, and use `|without` for fields consumed elsewhere.
- Use rendered fields, display configuration and Twig Tweak first. Write preprocess PHP only for a requirement those can't meet, and apply `CacheableMetadata` when you do.
- Never reproduce field rendering, access checks or cache metadata by hand.
- Scaffold every component with `drush generate four-sdc`, so each has the same files: `.component.yml`, `.twig`, `.scss`, optional `.js`, `.stories.twig`, `README.md`, `thumbnail.png`.
- CSS custom properties over Sass variables. Units in `to.rem()`, logical properties, breakpoints with `@include mq($from: …)`.

### 7.2 Component inventory

Mapped from the Figma Components page. "Skeleton" means the starterkit component is kept and restyled.

| SDC | Figma component | Origin |
|---|---|---|
| `logo` | Logo / Pimlico Road (dark, light), Logo / Grosvenor | new |
| `icon` | Icon / search, pause, play, menu, arrowL, arrowR, chevron-down, close, check, alert | new (SVG sprite in `assets/icons`) |
| `button` | A / Button (Dark, Yellow; default, hover, focus) | skeleton |
| `button_icon` | Media control (play, pause) | skeleton |
| `text_link` | Text link (tone, size, underlined) | new |
| `header` | Header (desktop, mobile) | skeleton |
| `main_menu` + `menu_link` | Menu link | skeleton |
| `mega_menu` | Mega menu (Brands, Newson's Yard, News, Visit) | new |
| `mobile_menu` + `mobile_nav_item` | Mobile menu, Mobile nav item | new (`<dialog>`) |
| `page_banner` | Page title (desktop, mobile) | skeleton |
| `hero_film` | Hero · Street film | new |
| `hero_split` | Hero / Split | new |
| `introduction` | Section · Introduction | new |
| `section_heading` | Section heading | new (extends `heading`) |
| `story_card` | A / Story card (default, hover, focus) | new (from `card`) |
| `story_row` | Story row · Mobile | new |
| `brand_card` | link:brand | new |
| `brand_list` | A / Brand list · hover | new |
| `feature_banner` | Yard banner, Newson's Yard section | new |
| `visit_banner` | Visit banner | new |
| `quote` | Quote | skeleton |
| `image_gallery` | Gallery rows, Gallery (swipe) on mobile | new (embla-carousel on mobile) |
| `info_row` | Info row, Info row · Mobile | new |
| `episode_row` | Episode row | new |
| `audio_player` | Audio player | new (plyr, already in composer) |
| `media_video` | Media / Video | skeleton `video`, restyled |
| `rich_text` | Rich text | new |
| `filter_link` | Filter link (default, active, hover, focus) | new |
| `pagination` | Pagination | new |
| `form` + `field` | Field (input, select, textarea; default, error, focus), Checkbox | skeleton `form`, extended |
| `newsletter` | Newsletter | new |
| `footer` | Footer | skeleton |
| `street_map` | Map · Overview, Map · Brand | new; wraps `<pimlico-map>` |
| `visually_hidden`, `messages`, `grid`, `view_clean` | | skeleton |

Not built: Event card and Event row (events are out of scope), and Toggle and Dialog, which were for cookie preferences and are replaced by CookieScript's own banner.

### 7.3 Design tokens

From the Figma variables, in `src/sass/init/_root.scss`:

```scss
:root {
  // Brand palette
  --color-brand-dark-grey: #394959;
  --color-brand-yellow: #f9e577;
  --color-brand-pumpkin: #e87400;
  --color-brand-white: #ffffff;
  --color-tint-linen-30: #f4f2eb;
  --color-tint-linen-60: #ece9df;

  // Semantic
  --color-text-primary: var(--color-brand-dark-grey);
  --color-text-inverse: var(--color-brand-white);
  --color-text-eyebrow: var(--color-brand-pumpkin);
  --color-action-primary: var(--color-brand-yellow);
  --color-action-on-primary: var(--color-brand-dark-grey);

  // Shape and spacing
  --radius-pill: 999px;
  --section-space: #{fluid(40px, 60px)};
  --page-gutter: #{fluid(24px, 80px)};
  --page-width: #{to.rem(1280px)};
}
```

- **Typography:** body and UI in **Lato 2.0** (300, 400, 500, 700), self-hosted and subset to Latin, because Google's Lato has no Medium. Body L 20/1.55 light, Body M 16/1.6, Body S 14/1.55. Labels: Nav 14 medium, tracking 0.14em; Eyebrow 12 bold, tracking 0.16em, uppercase, pumpkin. Display sizes 96, 64, 44 and 26, scaled with `fluid()` between 390 px and 1440 px.
- **Display serif: Fraunces** (confirmed 11 November 2026). It's on Google Fonts under the SIL Open Font License, so it's free for web use and can be self-hosted. Self-host the variable WOFF2 in `assets/fonts` rather than loading from Google, for performance and GDPR. Fraunces has optical size (`opsz`), `SOFT` and `WONK` axes: set `font-optical-sizing: auto`, and fix `SOFT` and `WONK` with `font-variation-settings` once matched against the Figma headings. Define it as `--font-family-display`, with Lato as `--font-family-body`.
- **Breakpoints:** the design has two artboards, 390 and 1440. Keep the starterkit's sass-mq set, and treat `tablet` (768) and `desktop` (980) as the main switches. The header and mega menu switch at `desktop`.
- **Layout:** the 1280 px content column with 80 px gutters maps directly onto the `contentWithGutters()` mixin.

### 7.4 Images and media

- Keep the skeleton's NIS image style sets: `l08` (16:9), `p05` (9:16), `sq` and `sc`.
- Add the ratios the design uses, generated in the same pattern: the reference project's `l23` set (4:3, gallery landscapes) and a new 4:5 portrait set for brand cards, story cards, gallery portraits (435 × 544) and the split hero (680 × 850). The reference project's ratio codes (`l08`, `l23`, `p05`, `p09`) aren't derivable from the ratio, so agree the 4:5 code with the lead developer before generating it. Add the matching `responsive_image.styles.aspect_ratio_*` and media view modes (`4_3`, `4_5`).
- Focal point on all image media. WebP conversion and quality 50 as in the reference project.
- Hero films are self-hosted MP4 in the `video` media type, autoplay muted with a poster, with a visible pause control (WCAG 2.2.2) and no autoplay under `prefers-reduced-motion`.

## 8. Custom modules

| Module | Responsibility |
|---|---|
| `pimlico_map` | The `pimlico_unit` entity, the tenancy feed route, the map render element and theme hook, the overview paragraph formatter, the brand-page pseudo-field, and the map picker widget |
| `pimlico_brands` | Brand-specific behaviour: the similar-brands fallback, the brands promo interstitial, and the `/dine` alias logic if the view can't express it |
| `pimlico_news` | Only if views and config can't express "featured story" and related-content fallback. Created lazily, not up front. |
| `pimlico_migrate` | Migration of the 47 brands and their Units from the map project's `map-source.json` (migrate_plus JSON source). Uninstalled after launch. |

PHP conventions, from the reference project:

- `declare(strict_types=1);` in every file, and `final class` by default.
- OOP hooks with `#[Hook('…')]` in `src/Hook/*Hooks.php`, with an "Implements hook_…" docblock. No new procedural hooks in `.module` files.
- Plugins declared with PHP attributes (`#[Block(…)]`, `#[FieldWidget(…)]`).
- Services named by class, with `autowire: true` and a dedicated logger channel (`logger.channel.pimlico_map`).
- Dependency injection everywhere. No `\Drupal::service()` in classes. The code samples in the map guide use static calls and must be rewritten to inject.
- Return types on every method. Class docblocks explain why, and link to the relevant ADR.
- Coding standard `Drupal,DrupalPractice` (phpcs through VS Code). Prettier and ESLint for JS.
- Tests in `tests/src/{Unit,Kernel,Functional}` with `#[Group('pimlico_map')]` attributes. Kernel tests are the default.

## 9. The map integration

The map is the one piece of real engineering on this site, and most of it is already done. `docs/PIMLICO-MAP-GUIDE.md` Part B in the map project is the detailed spec. This section records how it fits the project's conventions.

```
 pimlico-map project (source of truth)            Drupal (pimlico_map module)
 ───────────────────────────────────────          ─────────────────────────────────────────────
 map/pimlico-map.js       ── copied ──►  js/pimlico-map.js            (library pimlico_map/pimlico-map)
 output/map/pimlico-map.json ── copied ►  data/pimlico-map.json        (static, long cache)
                                          Brand ─► Unit ─► Map Buildings
                                          BrandFeedController ─► /api/map/brands (CacheableJsonResponse,
                                                                   tags node_list:brand, pimlico_unit_list)
 Browser: <pimlico-map src=".../pimlico-map.json" brands="/api/map/brands" …>  rendered by the street_map SDC
```

- **The component is a vendored, unmodified dependency.** Its two built files are copied into `pimlico_map` with a version number in the library definition. Fixes are made in the map project and copied in. ADR 0001 records this.
- **The `street_map` SDC wraps the element**, so the theme owns sizing, the heading and the category filter bar (built from `filter_link`). The module owns data and attributes.
- **Overview (T13):** the `street_map` paragraph renders the filter bar from `brand_category` terms in weight order, and the map with `locate` and search.
- **Brand page (T03):** a pseudo-field (`hook_entity_extra_field_info` and `hook_node_view` as OOP hooks) renders the map in `mode="brand"` with the Brand's map ID, placed by the view display beside the showroom details.
- **Picker:** a field widget on `field_map_buildings` shows the map in `pick` mode.
- **Feed contract:** exactly the shape in the guide's B3. A Kernel test asserts the shape, the active-only filter, and the cache tags.
- **Security:** same-origin fetches only. No CSP changes needed. OpenStreetMap attribution is always visible.

## 10. Cross-cutting concerns

| Concern | Approach |
|---|---|
| **Accessibility** | WCAG 2.2 AA. Native elements first: `<dialog>` for the mobile menu, `<details>` for transcripts, `<button aria-pressed>` for filters. Visible focus states (every Figma component has a Focus variant). Pause control on hero films. Storybook a11y addon on every component. Screen reader pass before launch. |
| **Performance** | Vite-built, per-component libraries. Responsive images with WebP. Hero video with poster and `preload="none"` on mobile. Page cache and dynamic page cache, with CloudFront in front (`cloudfront_cache_path_invalidate`, `cloudfront_cache_home_page`). The Base Map JSON is about 48 KB gzipped and cached long-term. |
| **SEO** | Metatag and Schema Metatag (`LocalBusiness` on Brand, `NewsArticle` on Article, `PodcastEpisode` on episodes), Pathauto patterns (`brands/[node:title]`, `news/[node:title]`), Simple XML sitemap (to add), robots.txt. |
| **Search** | Search API with the database backend, indexing Brands, Stories and Pages. Solr is unnecessary at this content volume. |
| **Forms** | Webform for Contact (with error and success states from the design), reCAPTCHA v3, SMTP delivery. |
| **Newsletter** | A block form posting to the client's email platform. The provider is an open task: build the `newsletter` component and block with a form that doesn't submit anywhere yet, and wire it up once the provider is chosen. |
| **Cookie consent** | **CookieScript** (decided 11 November 2026; set-up later). It's a hosted script, so no Drupal module is needed: the script tag is loaded first in `<head>` through a theme library, with its ID from environment config so staging and production can differ. The banner is styled in the CookieScript dashboard with the site's colours and fonts. The footer's Cookie preferences link opens it again (`CookieScript.instance.show()`). Google Tag uses CookieScript's Google Consent Mode v2 integration, so no tags fire before consent. |
| **Monitoring** | Sentry (Raven) for PHP and the Vite plugin for JS, as in the skeleton. Health check module. |
| **Security** | No secrets in config export. Contrib modules never patched in place (patches go in `patches/`). Feed is read-only public data. |

## 11. Open questions

1. **Newsletter provider:** Mailchimp, Dotdigital or something else? Left as an open task (§10).
2. **CookieScript set-up:** the account, banner configuration and script ID will be sorted later. The build leaves a slot for the script (§10).
3. **Map data:** the map project's open questions still apply and will be tackled later: base map source (OSM or Grosvenor survey), the unit register, tenancy status by hand or by dates, structured opening hours, short map labels, the six flagged brand placements, and a Newson's Yard site plan. Until then the migration uses the current placements.
4. **Brand map IDs:** will brands ever be renamed? If so, add a separate `field_map_id` rather than relying on the path alias.
5. **Hero films:** final footage, length and file size budget.
6. **Content migration:** beyond the 47 brands, is any news or page content migrated from belgraviavillage.com, or written fresh?
7. **Analytics:** GA4 through Google Tag, and who owns the container?

### Resolved

| Date | Question | Decision |
|---|---|---|
| 11 Nov 2026 | Repository | `jamesbriggs112/pimlico`, with `dev`, `staging` and `production` branches (§3) |
| 11 Nov 2026 | Display serif | Fraunces, self-hosted (§7.3) |
| 11 Nov 2026 | Events | Not required (§2.2) |
| 11 Nov 2026 | Cookie consent | CookieScript, set up later (§10) |

## 12. Architecture decisions to record

Created in `docs/adr/` during phase 0, in the reference project's format (imperative title, prose decision and rejected alternatives, optional Consequences):

- `0001-keep-the-map-component-as-an-unmodified-dependency.md`
- `0002-link-brands-to-the-map-through-units.md`
- `0003-model-units-as-a-custom-content-entity.md`
- `0004-model-each-kind-of-story-as-its-own-bundle.md`
- `0005-serve-the-tenancy-feed-from-a-cacheable-controller.md`
- `0006-build-every-visual-pattern-as-a-single-directory-component.md`
