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
- Install the `jp_drupal_skeleton.sql`, and update the settings accordingly at the bottom of `web/sites/default/settings.local.php` found here: https://www.dropbox.com/s/6mkd6vvehx2bhvo/jp_drupal_skeleton.sql?dl=0
- Point Document root to the `web` directory
- `cd web/themes/custom/four`
- `yarn`
- `yarn watch` to build a dev version, start up a dev server and watch files
