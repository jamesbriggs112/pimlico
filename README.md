# Pimlico Road

The website for Pimlico Road, the Belgravia street of antique dealers, makers, fabric houses, lighting and interior designers, built by Four for Grosvenor on Drupal 11.

It gives every Brand on the street its own page, opening hours and place on an interactive street map, and publishes news, podcast episodes and press coverage.

## At a glance

| | |
|---|---|
| **CMS** | Drupal 11, PHP 8.4, Gin admin theme |
| **Theme** | `web/themes/custom/pimlico`: Single Directory Components (SDC), Sass (BEM, custom properties, sass-mq), Vite |
| **Street map** | The Pimlico Road map component, fed by `/api/map/brands` (`pimlico_map`) |
| **Search** | Search API, grouped into Brands, Dine, News and Press (`pimlico_search`) |
| **Hosting** | AWS Elastic Beanstalk (staging and production), deployed by GitHub Actions |
| **Designs** | Figma file `REw9zZENN4OEozQHrt17pD` |

## Documentation

| Document | What it covers |
|---|---|
| [docs/TAD.md](docs/TAD.md) | Technical architecture: content model, users, integrations, hosting, launch, open questions |
| [docs/build-plan.md](docs/build-plan.md) | The build, phase by phase |
| [docs/local-development.md](docs/local-development.md) | Setting up the site locally |
| [docs/front-end-conventions.md](docs/front-end-conventions.md) | How the theme is written: components, BEM, tokens, breakpoints, mixins, JS, tests |
| [docs/editor-guide.md](docs/editor-guide.md) | Short guide for content editors |
| [docs/launch-checklist.md](docs/launch-checklist.md) | Pre-launch checks and their status |
| [docs/adr/](docs/adr/) | Architecture decisions |

## Getting started

You need PHP 8.4, Composer 2, MySQL, Node 22, pnpm and Laravel Valet. The full steps are in [docs/local-development.md](docs/local-development.md). In short:

```bash
composer install
cp web/sites/example.settings.local.php web/sites/default/settings.local.php   # then set the database
(cd web && valet link pimlico && valet secure pimlico)
vendor/bin/drush si --existing-config
vendor/bin/drush php:script scripts/dev/seed-pages.php
vendor/bin/drush php:script scripts/dev/seed-brand-content.php
```

The site is then at https://pimlico.test. Log in with `vendor/bin/drush uli`.

### Theme

```bash
cd web/themes/custom/pimlico
pnpm i
pnpm dev     # Vite dev server on :5173; Drupal uses it automatically while it runs
pnpm build   # production build into dist/
pnpm lint
```

Twig templates are thin adapters onto the components in `components/`. Preprocess functions live in `includes/*.inc`.

## Custom modules

| Module | What it does |
|---|---|
| `pimlico_map` | Units (shopfronts), the Unit map picker, the map on Brand pages and the `/api/map/brands` feed |
| `pimlico_brands` | Groups Brand categories into the Brands page filters (Interiors, Antiques & art) |
| `pimlico_search` | Search result groups, `/llms.txt`, and the sitemap line in `robots.txt` |
| `pimlico_migrate` | Imports the Brands, Units and Brand photos. Uninstall after launch |
| `pimlico_admin` | Pimlico Road branding for the CMS on top of Gin |
| `cloudfront_cache_home_page`, `custom_image_styles`, `four_sdc` | From the Four Drupal skeleton |

## Working on the site

- **Branches:** `feature/…`, `bugfix/…` or `hotfix/…`, then a pull request into `dev`, then `dev` into `staging`, then `staging` into `production`.
- **Config:** export with `drush cex` and commit it with the code that needs it. Menus, taxonomy terms and custom blocks go through Structure Sync (`drush em`, `drush et`, `drush eb`); import taxonomies in safe mode.
- **Checks:** `vendor/bin/phpcs`, `vendor/bin/phpunit --testsuite custom`, and `pnpm lint` and `pnpm test` in the theme. Pull requests run phpcs and the tests.
- **Secrets:** never commit keys or passwords. Environment values are Elastic Beanstalk properties.
- **Accessibility:** WCAG 2.2 AA. Use native elements, visible focus states, and respect `prefers-reduced-motion`.

## Deployment

Merging into `staging` or `production` deploys that branch to its Elastic Beanstalk environment. The server then runs `drush deploy` (database updates, config import, cache clear). See [Hosting and domains](docs/TAD.md#hosting-and-domains) in the TAD.

## Updating Drupal

```bash
composer update "drupal/core-*" --with-all-dependencies   # or drupal/<module> --with-dependencies
vendor/bin/drush updb
vendor/bin/drush cr
```
