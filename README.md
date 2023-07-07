# Four Agency - Drupal Skeleton

## IMPORTANT
This repository should only be used as an upstream source for projects.

### Prerequisites
- Node v16
- Yarn
- PHP 8.1
- Composer 2

### Using this repository as a project base

- `composer install`
- Import `jp_drupal_skeleton.sql`, found here: https://www.dropbox.com/s/6mkd6vvehx2bhvo/jp_drupal_skeleton.sql?dl=0
- Copy `example.settings.local.php` into `web/sites/default/settings.local.php` and update database connection info toward the bottom of the file
- Copy `example.settings.php` into `web/sites/default/settings.php` and update database connection info toward the bottom of the file
- Copy `example.development.services.yml` into web/sites/default/development.services.yml` for your local development debugging etc. to be enabled
- Point Document root to the `web` directory
- `cd web/themes/custom/four`
- `yarn`
- `yarn watch` to build a dev version, start up a dev server and watch files
