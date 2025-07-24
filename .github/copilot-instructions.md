# Copilot Instructions for Four Drupal Skeleton

## Project Overview

- This is a Drupal 10 skeleton project, intended as an upstream source for new Drupal builds at Four Agency.
- The project uses Composer for PHP dependencies, pnpm for JS, and Drush for Drupal CLI tasks.
- Custom theming is handled in `web/themes/custom/`, with a starterkit (`four_starterkit`) and support for component-driven development.

## Key Workflows

- **Initial Setup:**
  - `composer install` (from project root)
  - `pnpm install`
  - Copy `web/sites/example.settings.local.php` to `web/sites/default/settings.local.php` and configure DB settings.
  - Use Drush for site install/import: `drush si --existing-config && drush ib`
- **Theme Development:**
  - Work in `web/themes/custom/<theme>`
  - Use `pnpm dev` for live-reloading and asset builds
  - Add new SASS/JS components in `components/` and declare in `libraries.yml`
  - Use CSS variables and SASS mixins from `sass/init/`
- **Config Management:**
  - Export config: `drush cex`
  - Import config: `drush cim && drush cr`
  - Use [Structure Sync](https://www.drupal.org/project/structure_sync) for blocks, menus, taxonomies
- **Deployment:**
  - AWS stack recommended (see Bitbucket pipeline and environment variables in README)
  - Use `composer update` and Drush for core/module updates

## Project Conventions

- **DRY Principle:**
  - Apply the "Don't Repeat Yourself" principle wherever possible. Reuse mixins, variables, and shared components to minimize duplication in SASS, JS, and PHP code.
- **Twig Debugging:** Enable with Drush state commands (see README)
- **Component CSS/JS:**
  - Files in `components/` generate their own bundles; declare in `libraries.yml` and attach in Twig with `{{ attach_library('<theme>/paragraph-accordion') }}`
  - Prefix SASS/JS files with `_` to avoid separate bundle, import into main files
    **Breakpoints:**
  - Set in `sass/init/_mq-settings.scss` using [sass-mq](https://github.com/sass-mq/sass-mq)
    **SASS Mixins:**
  - Use `fluid`, `contentWithGutters`, `contentGrid` from `sass/init/_mixins.scss`
    **sass-rem:**
  - Use the [`sass-rem`](https://github.com/pierreburel/sass-rem) mixin to convert px to rem units for accessibility and scalability. Example usage:
    `scss
@use '~sass-rem' as to;
.root {
  font-size: to.rem(18px);
}
`
    **CSS Variables:**
  - Use variables from `sass/init/_root.scss`

## Integration Points

- **Drush:** Used for all Drupal CLI tasks (install, config, cache, updates)
- **Composer:** PHP dependency management
- **pnpm:** JS dependency management and theme asset builds
- **AWS/Bitbucket Pipelines:** For deployment (see README for required env vars)

## References

- Main project config: `composer.json`, `bitbucket-pipelines.yml`, `config/`
- Theme starter: `web/themes/custom/four_starterkit/`
- Example settings: `example.settings.local.php`, `web/sites/example.settings.local.php`
- See `README.md` for full setup, workflow, and deployment details

---

**When in doubt, check the main README.md for up-to-date workflow and conventions.**
