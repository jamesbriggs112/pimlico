# Four Agency - Drupal Skeleton

## IMPORTANT
This repository should only be used as an upstream source for projects.

### Prerequisites
- Node v16
- Yarn
- Composer 2

### Using this repository as a project base

- `composer install`
- Install the `four-base-db.sql` found in the root, and update the settings accordingly at the bottom of `web/sites/default/settings.local.php`
- Point MAMP Document root to the `web` directory
- `cd web/themes/custom/four`
- `yarn`
- `yarn watch` to build a dev version, start up a dev server and watch files
