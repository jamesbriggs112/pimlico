# Four Agency - Drupal Skeleton

## IMPORTANT
This repository should only be used as an upstream source for projects.

### Prerequisites
- Node v18
- Yarn 3
- PHP 8.2
- Composer 2

### How do I get set up? ###

* Install Drush https://github.com/drush-ops/drush-launcher#installation---phar
* `composer install` from your Drupal root folder
* Copy `web/sites/example.settings.local.php` to `web/sites/default/settings.local.php` and configure database
* Reset the admin password by running `drush uli --uri http://LOCAL_HOSTNAME`
* Make sure the files from the backup are copied into the `/web/sites/default/files` folder
- Point Document root to the `web` directory
- `cd web/themes/custom/four`
- `yarn`
- `yarn watch` to build a dev version, start up a dev server and watch files