# Pimlico Road
## Technical Architecture Document

| Date | Author | Notes |
|---|---|---|
| 11 November 2026 | James Briggs | Initial draft, with the build plan |
| 11 November 2026 | James Briggs | Rewritten as built (phases 0–7), in the format of the Grosvenor B2B TAD |

The build plan that goes with this document is [build-plan.md](build-plan.md). The launch checklist is [launch-checklist.md](launch-checklist.md) and the editor guide is [editor-guide.md](editor-guide.md). The domain language (Brand, Unit, Map Building, Story…) is defined in [CONTEXT.md](../CONTEXT.md).

## Contents

1. [Background and overview](#background-and-overview)
2. [Site map and structure](#site-map-and-structure)
3. [Technical architecture](#technical-architecture)
4. [Specific considerations](#specific-considerations)
5. [Content types](#content-types)
6. [Hosting and domains](#hosting-and-domains)
7. [Tracking](#tracking)
8. [Testing](#testing)
9. [Deploying to live](#deploying-to-live)
10. [Post-launch actions](#post-launch-actions)
11. [Open questions](#open-questions)
12. [Appendix A: Build conventions](#appendix-a-build-conventions)
13. [Appendix B: Theme architecture](#appendix-b-theme-architecture)
14. [Appendix C: Custom modules](#appendix-c-custom-modules)
15. [Appendix D: The street map](#appendix-d-the-street-map)
16. [Appendix E: Architecture decisions](#appendix-e-architecture-decisions)

---

## Background and overview

Four has been briefed by Grosvenor to build a website for Pimlico Road, one street in Belgravia: its brands (showrooms, dealers, restaurants and cafés), Newson's Yard, news and visitor information. It replaces the Pimlico Road pages of belgraviavillage.com.

The site is built in Drupal. Its distinctive feature is an interactive bird's-eye map of the street, which lights up each brand's building and is shown on the Visit page and on every brand page. The map was built and tested as a separate project and is brought into Drupal unchanged.

The content includes photography, background films, YouTube videos, a podcast player and a contact form. There is no commerce, membership or CRM integration.

## Site map and structure

Link to designs: Figma "Pimlico Visuals – V01" (`REw9zZENN4OEozQHrt17pD`), pages 01 Desktop, 02 Mobile, 03 Brand with Map and Components.

| Ref | Page | Built as |
|---|---|---|
| T01 | Home | Page with sections |
| T02 | Brands: listing with category filter (All, Interiors, Antiques & art), pagination and a promo band | `brands` view |
| T02b | Dine: places to eat and drink, filtered by Pubs and restaurants or Cafés | `brands` view |
| T03 | Brand, with "Find the showroom" and the map | Brand content type |
| T06 | Newson's Yard | Page with sections |
| T07 | News: featured story, grid, News/Press filter, pagination | `news` view |
| T08 | News article | Article content type |
| T10 | Podcast episode: player, notes, chapters, transcript | Podcast episode content type |
| T12 | Press article: publication, source link | Press article content type |
| T13 | Visit: the street map with category filter, location, getting here | Page with sections |
| T14 | About Pimlico Road | Page with sections |
| T15 | Contact: enquiry routes, form (inline validation), success message | Page with sections and a Webform |
| T16 | Privacy, Cookies, Accessibility | Pages with a Policy text section |
| T17/T18 | Search and search results, grouped by Brands, Dine, News and Press | `search` view |

Global elements: a header with five top-level links (Brands, Dine, Newson's Yard, News, Visit) and search, a mobile menu, the newsletter sign-up, and a footer with Explore, Information and Follow menus, legal links and the Pimlico Road and Grosvenor logos.

Out of scope: events (confirmed not required, 11 November 2026), accounts or a members' area, and multilingual content (the site is English only).

## Technical architecture

### General application structure

The site is built on **Drupal 11** (11.4) and PHP 8.4, starting from Four's `four-drupal-skeleton`. AWS CloudFront sits between visitors and the site, caching pages and assets close to them; Drupal purges the pages that change when an editor saves (see [Caching](#caching)).

The site is hosted on AWS Elastic Beanstalk. The database is held in a managed relational database service and uploaded media in shared file storage, so the application servers hold no state and can scale.

Within the CMS, pages are built from a palette of **sections** (Paragraphs) that editors arrange in any order: heroes, introductions, cards, banners, galleries, info rows, the street map, forms and so on. Brands and stories are **structured content types** with fixed fields, so every brand page and story follows the design.

The front end is one custom theme, `pimlico`. Every visual pattern is a Single Directory Component (SDC), documented in Storybook, and built with Vite. Drupal creates the responsive image variants and serves them as WebP.

Drupal also provides the metadata for search engines and AI search: meta tags and Open Graph on every page and listing; schema.org structured data (Organization on the home page, LocalBusiness with address, phone and website for each brand, Article for stories, and a BreadcrumbList on every page); an XML sitemap linked from `robots.txt`; and `/llms.txt`, a plain-text summary of the site with every brand and the latest stories, built from published content (pimlico_search).

### Target specification

The site targets every browser with 1% or more UK share, achieved through responsive code and testing at the design's two artboards (390px and 1440px) and between. In practice:

- Chrome, Edge and Firefox (latest two versions) on desktop.
- Safari (latest two versions) on macOS and iOS.
- Chrome and Samsung Internet on Android.

It meets **WCAG 2.2 AA**. See [Testing](#testing).

## Specific considerations

### Authentication

Only the client's editors log in, with Drupal accounts at `/user/login` (the Gin admin theme, branded with the Pimlico Road logo, colours and login photography). There is no public login. The login, password reset and registration forms have a CAPTCHA point set to reCAPTCHA; the reCAPTCHA module and its keys (per environment) are still to be enabled before launch.

### Users and permissions

#### Public facing

Visitors are anonymous. They can browse everything published, search, use the map, sign up to the newsletter and send the contact form.

#### Admin

| Role | Can |
|---|---|
| **Administrator** | Everything, including configuration. Four only. |
| **Content editor** | Create, edit, publish and delete all content (pages, Brands, Units, stories), media and categories; manage menus. |
| **Writer** | Create and edit their own content and save drafts; an editor publishes. |

Content goes through a simple **Draft → Published** workflow (Content Moderation) on every content type.

### Traffic and load

No special traffic or load measures are expected. The site is mostly static pages served from the page cache and CloudFront; the map's data feed is cached too. Elastic Beanstalk can add instances if needed.

### Optimisation and responsive

#### Caching

The site has three levels of caching:

- **CloudFront CDN** in front of the site. When an editor saves the Home page, a Brand or a Unit, Drupal invalidates the affected paths (`/`, `/api/map/brands`).
- **Drupal's page cache and dynamic page cache**, invalidated by cache tags the moment content changes.
- **Long-lived static assets:** CSS, JS, images and the base map file carry version strings, so they can be cached for a year.

#### Images

- Responsive images in WebP, at the sizes each slot needs. Drupal creates all variants and stores them on the shared file storage; CloudFront caches them.
- Focal point on image media, so crops keep the subject.
- The main image at the top of each page loads first ("Hero" media view mode, eager loading); everything else loads lazily.

#### Media library

The standard Drupal media library, with Image, Video (uploaded films), Remote video (YouTube and Vimeo links), Audio (podcasts) and Document types.

#### Video hosting

- **Background films** (Home, Newson's Yard heroes) are short silent loops uploaded as MP4. Budget: 10–20 seconds and 3–5 MB. They don't download until the page has loaded, and don't play for people who prefer reduced motion, use data saver or are on a slow connection.
- **Other video** goes on YouTube (or Vimeo) and is added as a Remote video. YouTube videos show their thumbnail and the site's play button; nothing loads from YouTube until someone presses play, and playback uses YouTube's privacy-enhanced (no-cookie) player in the site's player controls.

#### Front-end performance

Measured locally on a throttled phone (slow 4G, 4× CPU): largest contentful paint is Home 2.6 s, Brand 0.9 s, Visit 0.7 s and News 0.8 s, with no layout shift. Each component's CSS and JS loads only on pages that use it.

### Forms and data collection

| Form | Collects | Stored | Sent to |
|---|---|---|---|
| **Contact** (Webform) | Name, email, enquiry type, message | Webform submissions in Drupal | Email to the client (recipients to confirm) |
| **Newsletter** (Webform) | Email address | Not stored; the provider is to be chosen | The newsletter provider, once chosen |

- Both forms validate inline as people type and on submit, with messages under each field that don't move the layout, and Drupal checks again on the server.
- **Spam protection:** not yet in place on either form. The recommendation is the Honeypot module, which works with Webform and adds nothing visible, with reCAPTCHA as a fallback if spam still gets through.
- **GDPR:** the newsletter form exists only to sign up. The visitor enters their own email and presses Subscribe, and the note under the form says what they'll get, that they can unsubscribe at any time, and links to the privacy notice. That is clear, affirmative consent, so no separate tick-box is needed. The provider should record when and where each person signed up; double opt-in is recommended.
- Contact form submissions hold personal data. Set a retention period with the client (for example, delete after 12 months), and name Drupal as a processor in the privacy notice.

### Integration points

#### The street map

The map component, its base map and the data pipeline live in the separate map project. Drupal serves the list of active brands and the buildings they occupy at `/api/map/brands`, and editors place each shopfront (Unit) by clicking it on the map. Details are in [Appendix D](#appendix-d-the-street-map).

#### Brand import from belgraviavillage.com

The 47 Pimlico Road brands, their shopfronts, categories, addresses, opening hours, websites and photography were imported from the current belgraviavillage.com pages through the map project's data, by the `pimlico_migrate` module. The import is re-runnable and is uninstalled after launch.

#### Newsletter provider

To be chosen (open question). The sign-up form is built and styled but doesn't post anywhere yet.

#### CookieScript and Google Tag

See [Tracking](#tracking).

### Search

Search API with the database backend (Solr isn't needed at this content volume); Drupal's core search is off. It indexes Brands and Stories. Results are grouped (Brands, Dine, News, Press) with counts in the filter bar, three top hits per group, and every result with a pager once a group is chosen. Before a search, the page shows popular searches and suggestions.

## Content types

### Brand

Fixed fields:

- **Brand:** title, category, summary, hero image.
- **About:** heading, text, pull quote, gallery and photography credit.
- **What you'll find:** specialisms.
- **Find the showroom:** address, opening hours, trade note, email, phone, website, accessibility link.
- **Map:** Units, tenancy status, display on map, short map label.
- **Similar brands:** up to three, otherwise three from the same category.
- **SEO:** title, social media title, description, image.

### Article

- **Story:** title, news category, summary, lead image and caption, author, reading time, credits, featured flag, featured Brand.
- **Sections:** Text, Image, Quote, Gallery, Video and other sections.
- **Related stories:** chosen, otherwise from the same category.
- **SEO:** the shared set.

### Podcast episode

As Article, plus:

- **Episode:** audio file, episode number, duration, guest.
- **Chapters:** time and topic.
- **Transcript.**

### Press article

As Article, plus:

- **Coverage:** publication and its logo, publication date, journalist, link to the original.

### Page

- **Page:** title and summary.
- **Sections:** Hero film, Split hero, Introduction, Cards, Feature banner, Visit banner, Info rows, Info band, Split text, Rich text, Image, Gallery, Quote, Video, Street map, Policy text, Next step, Contact form.
- **SEO:** the shared set.

### Unit (not public)

A shopfront that Brands trade from: name, address, area and its building(s) on the map, set with the map picker. Units have no public page.

### Categories (taxonomy)

| Vocabulary | Terms | Used for |
|---|---|---|
| Brand category | Interiors, Antiques & art, Food & Drink (with Pubs and restaurants, Cafés) | The filters on Brands (Interiors, Antiques & art), Dine (Pubs and restaurants, Cafés) and the street map (the three top-level terms), and card labels. Editors choose from the categories the site shows; Food & Drink is a heading, not a choice. |
| News category | Interiors, Antiques & art, Food & drink, Makers, The street | Story labels and related stories |
| Area | Pimlico Road, Newson's Yard, Ebury Street, Bourne Street, Holbein Place | Grouping Units |

## Hosting and domains

### Source code

The source code is in GitHub: `git@github.com:Four-Agency/pimlico-website.git`, with branches `dev`, `staging` and `main` (production). The agency skeleton is the `upstream` remote, for skeleton updates.

Branches follow the format `feature/…`, `bugfix/…` and `hotfix/…`. These are merged into `dev` by pull request, then `dev` into `staging`, then `staging` into `main` (production).

### Environments

**Development** is done locally with Laravel Valet at `https://pimlico.test` ([local-development.md](local-development.md)).

Deployment is by GitHub Actions: merging a pull request into `staging` or `main` deploys that branch to its Elastic Beanstalk environment. After deploying, the server runs `drush deploy`, which runs database updates, imports configuration and clears caches. Developers export configuration with `drush cex` and commit it with the code that needs it.

There are two server environments:

- **Staging** is the final checkpoint before going live, used for content entry and client review. It stays after launch for ongoing development. It is set to `noindex`.
- **Production** is the live site.

Each environment's values are Elastic Beanstalk environment properties, so no domain or secret is committed:

| Property | Value |
|---|---|
| `ENVIRONMENT` | `staging` or `production` |
| `SITE_HOST` | The public host name (trusted host and sitemap base URL) |
| `HASH_SALT` | A long random string per environment |
| `SENTRY_DSN`, `SENTRY_ENVIRONMENT`, `SENTRY_RELEASE` | Error reporting |
| `SMTP_USERNAME`, `SMTP_PASSWORD` | Mail delivery |

Developers maintain their own local environments, not included above and not visible to anyone else.

### Domains

| Environment | Domain |
|---|---|
| Staging | To be confirmed (`*.preflight.site`) |
| Production | To be confirmed |

## Tracking

### Cookies

Cookie consent is handled by **CookieScript**, loaded first in the page `<head>` with a script ID per environment. The banner is styled in the CookieScript dashboard with the site's colours and fonts, and the footer's Cookie preferences link reopens it. Google Tag uses CookieScript's Google Consent Mode v2 integration, so no tags fire before consent. This waits on the CookieScript account.

The site sets no cookies of its own for visitors. YouTube sets none until someone presses play.

### Tracking details

GA4 through Google Tag, with consent mode. The events to track, and who owns the Google account and container, are to be confirmed with the client.

## Testing

The site should be tested on all the browsers listed in [Target specification](#target-specification). Results so far are recorded in [launch-checklist.md](launch-checklist.md):

- **Accessibility:** axe finds no WCAG 2.2 AA issues on 17 templates at 390px and 1440px. Keyboard focus is visible throughout, nothing scrolls sideways at 200% zoom, and films respect reduced motion.
- **Map:** the map project's 96 browser checks (`scripts/map-qa.sh`) all pass against the Drupal pages.
- **Code:** automated tests (Kernel), Drupal coding standards, ESLint and Prettier all pass; `composer audit` is clean.
- **Still to do by a person:** VoiceOver on macOS and iOS, Safari and iOS devices (especially the map's pinch and full screen), Android Chrome and Firefox.

All standard Drupal pre-launch checks should also be done (status report, cron, error logs, file permissions).

## Deploying to live

### Launch process

The site is a new build. Brands come from belgraviavillage.com through the import; other pages and stories are written fresh.

1. Content is entered on staging. Before launch, agree a content freeze with the client.
2. Copy staging's database and media files to production.
3. Set the production environment properties and DNS, and generate the sitemap.

[launch-checklist.md](launch-checklist.md) has the first-deploy and go-live steps.

### Redirects

The Pimlico Road pages of belgraviavillage.com should redirect to the new site:

| URL | Points to | Redirect type | Notes |
|---|---|---|---|
| `belgraviavillage.com/pimlico-road` | `/` | 301 | |
| `belgraviavillage.com/brand/*` (Pimlico Road brands) | `/brands/*` | 301 | One per brand; the slugs differ, so a list is needed |

The full list waits on the client and on who controls belgraviavillage.com.

## Post-launch actions

- **Destroy unused environments:** none. Staging remains after launch for ongoing development.
- Uninstall `pimlico_migrate` once the import is final.
- Submit the sitemap in Google Search Console, and set up uptime monitoring on `/` and `/api/map/brands`.

## Open questions

1. **Newsletter provider:** Mailchimp, Dotdigital or something else?
2. **CookieScript:** the account, banner and script ID.
3. **Domains:** staging and production.
4. **Contact form:** recipients, routing by enquiry type, and how long submissions are kept.
5. **Spam protection:** Honeypot on the forms, and reCAPTCHA keys for the login forms.
6. **Analytics:** events to track, and the account owner.
7. **Map data:** the flagged placements and the Newson's Yard layout (to check with the client in the map picker); the base map source (OpenStreetMap or a Grosvenor survey).
8. **Brand data:** six missing postcodes; Food & Drink sub-categories; whether more brands belong in Antiques & art; The Orange, no longer listed on belgraviavillage.com.
9. **Hero films:** final footage within the 3–5 MB budget.
10. **Redirects:** the list of belgraviavillage.com URLs.

### Resolved

| Date | Question | Decision |
|---|---|---|
| 11 Nov 2026 | Repository | GitHub `jamesbriggs112/pimlico`, branches `dev`, `staging`, `production` |
| 13 Nov 2026 | Repository | Moved to GitHub `Four-Agency/pimlico-website`; branches `dev`, `staging` and `main` (`main` replaces `production` and deploys to production) |
| 11 Nov 2026 | Display font | Fraunces, self-hosted |
| 11 Nov 2026 | Events | Not required |
| 11 Nov 2026 | Cookie consent | CookieScript, set up later |
| 11 Nov 2026 | Navigation | Top-level links only, no dropdowns; filters live on the listing pages |
| 11 Nov 2026 | Brands filter | As Figma: All, Interiors, Antiques & art. Food & Drink is on Dine. |
| 11 Nov 2026 | Newsletter consent | No tick-box needed for a sign-up-only form |

---

## Appendix A: Build conventions

These follow the lead developer's reference project (Sheffield Chamber of Commerce). New names use the same patterns.

| Thing | Convention | Examples |
|---|---|---|
| Theme | short client machine name | `pimlico` |
| Client modules | `pimlico_*`, `package: Pimlico` | `pimlico_map`, `pimlico_brands`, `pimlico_search`, `pimlico_migrate`, `pimlico_admin` |
| Node bundles | snake_case singular nouns | `brand`, `article`, `podcast_episode`, `press_article`, `page` |
| Paragraph types | snake_case. New types are named for the SDC they render; types inherited from the skeleton keep their IDs (`cta` renders `next_step`, `content_grid` renders `card_grid`, `text_wysiwyg_` renders `rich_text`) | `hero_film`, `street_map`, `info_band`, `policy_content` |
| Vocabularies | `{context}_{type}` | `brand_category`, `news_category`, `area` |
| Fields | `field_` + snake_case, no bundle prefix, storage reused across bundles | `field_heading`, `field_text`, `field_image`, `field_sections` |
| Views | plural nouns | `brands`, `news`, `search` |
| Menus | kebab-case | `main`, `footer`, `footer-information`, `legal`, `social-media` |
| SDC folders | snake_case nouns | `story_card`, `info_row`, `youtube_player` |
| CSS classes | BEM, block = the component in kebab-case | `.story-card__title`, `.info-band--inset` |
| CSS custom properties | brand, then semantic, then component | `--color-brand-yellow`, `--color-text-eyebrow`, `--button-hover-background-color-yellow` |
| JS behaviours | camelCase, in an IIFE with `once` and `Drupal` | `Drupal.behaviors.formValidation` |
| ADRs | `docs/adr/NNNN-imperative-kebab-title.md` | `0001-keep-the-map-component-as-an-unmodified-dependency.md` |

PHP:

- `declare(strict_types=1);` everywhere, `final class` by default, and return types on every method.
- OOP hooks with `#[Hook]` in `src/Hook/*Hooks.php`; plugins with PHP attributes; services autowired and named by class.
- Dependency injection in classes; theme preprocess (`includes/*.inc`) may use `\Drupal::service()`.
- Coding standard `Drupal,DrupalPractice`; Kernel tests by default.

Configuration is exported to `config/sync`. Taxonomy terms, menu links and custom blocks are exported with Structure Sync (`drush et`, `drush em`, `drush eb`). Config Ignore keeps editor-managed config (webforms other than Contact and Newsletter) as edited on the server.

## Appendix B: Theme architecture

The theme, `pimlico`, was generated from `four_starterkit` and keeps the reference project's layout:

- `components/`: the SDCs.
- `templates/`: Drupal templates.
- `includes/*.inc`: preprocess, one file per area.
- `src/sass`: tokens, typography and layout.
- `assets/`: fonts, icons and brand files.

Rules:

- Drupal templates are thin adapters that map fields onto components with `include`/`embed` and `only`.
- Preprocess is written only where display configuration and Twig Tweak can't do the job, with cache metadata applied.
- Every component has `.component.yml`, `.twig`, `.scss`, `README.md` and `thumbnail.png`, with `.js` where it needs behaviour and `.stories.twig` where it can be shown on its own (29 of 51). See [front-end-conventions.md](front-end-conventions.md).

Design tokens from the Figma variables are CSS custom properties in `src/sass/init/_root.scss`:

- **Palette:** dark grey `#394959`, yellow `#f9e577`, pumpkin `#e87400`, with eyebrows in pumpkin `#e87400` at the client's request. That is 3:1 on white, below the WCAG AA 4.5:1 for small text; `#a64f00` (5:1) is kept as a token if this is revisited.
- **Linens:** `#f4f2eb` and `#ece9df`.
- **Layout:** a 1280px page width and fluid spacing between 390px and 1440px.

**Typography:**

- Body and labels are Lato 2.0 (300, 400, 500, 700); headings are Fraunces (variable). Both are self-hosted.
- Label and body styles always set Lato, even on heading elements.

**Breakpoints:** sass-mq with tablet (768px) and desktop (980px).

Main components, by area:

| Area | Components |
|---|---|
| Primitives | `logo`, `icon`, `button`, `button_icon`, `text_link`, `heading`, `form`, `visually_hidden`, `messages` |
| Layout | `header`, `main_menu`, `menu`, `footer`, `newsletter`, `page_title`, `page_banner`, `pagination`, `filter_bar`, `grid` |
| Heroes and banners | `hero_film`, `hero_split`, `feature_banner`, `visit_banner`, `info_band`, `promo_band` |
| Sections | `introduction`, `section_heading`, `rich_text`, `quote`, `card_grid`, `story_card`, `image_gallery`, `info_rows`, `info_row`, `split_section`, `next_step`, `form_section`, `policy_content`, `figure` |
| Stories | `article`, `featured_story`, `story_intro`, `story_credits`, `episode_band`, `audio_player` |
| Media | `media_video`, `youtube_player` |
| Map | `street_map` |
| Drupal wrappers | `block`, `paragraph`, `view_clean` |

The admin theme is Gin, branded by the `pimlico_admin` module (Fraunces page titles), Gin settings (navy accent, the Pimlico Road CMS icon) and Gin Login (wordmark and photography).

## Appendix C: Custom modules

| Module | Responsibility |
|---|---|
| `pimlico_map` | The Unit entity, the brand feed at `/api/map/brands`, the map on Brand pages, the Unit map picker, and CloudFront purging of the feed. |
| `pimlico_brands` | The Brands and Dine category filters (a contextual filter, so pager links keep `?category=`), card labels and Brand breadcrumbs, and the Brand form's category list grouped as Brands and Dine. |
| `pimlico_search` | Sorts indexed content into search groups (Brands, Dine, News, Press). |
| `pimlico_migrate` | Imports Brands, Units and photos from the map project's data and belgraviavillage.com. Uninstalled after launch. |
| `pimlico_admin` | CMS branding on top of Gin. |

Each has Kernel tests where it has logic (`pimlico_map`, `pimlico_brands`, `pimlico_search`).

## Appendix D: The street map

The map project (`pimlico-map`) is the source of truth for the map component (`pimlico-map.js`) and the base map (`pimlico-map.json`, built from OpenStreetMap). Both are copied unchanged into `pimlico_map/assets`, with the version number in the library definition; the version is also the base map's cache-busting `?v=`.

- **Data:** a Brand links to one or more Units, and each Unit to one or more Map Buildings. `/api/map/brands` lists published, active Brands shown on the map, with their buildings and top-level category, in the shape the component expects. It is cached by tag and purged from CloudFront on save.
- **Visit page:** the Street map section shows the map with the category filter, search and "show my location".
- **Brand pages:** the map is zoomed to the Brand, beside the showroom details.
- **Editors:** the Unit form shows the map in pick mode; clicking a building fills its ID.
- **QA:** `scripts/map-qa.sh` runs the map project's 96 browser checks against the Drupal pages.
- **Security:** same-origin requests only; OpenStreetMap attribution is always shown.

Updating the map: rebuild and test in the map project, copy the two files, bump the library version, run `scripts/map-qa.sh`.

## Appendix E: Architecture decisions

Recorded in `docs/adr/`:

- `0001-keep-the-map-component-as-an-unmodified-dependency.md`
- `0002-link-brands-to-the-map-through-units.md`
- `0003-model-units-as-a-custom-content-entity.md`
- `0004-model-each-kind-of-story-as-its-own-bundle.md`
- `0005-serve-the-tenancy-feed-from-a-cacheable-controller.md`
- `0006-build-every-visual-pattern-as-a-single-directory-component.md`
