# Four Agency - Drupal Skeleton

## IMPORTANT

This repository should only be used as an upstream source for projects.

### Prerequisites

- Node v20
- pnpm
- PHP 8.3
- Composer 2
- ImageMagick: `brew install imagemagick`

Drush 12 no longer works with drush-launcher so add the following to your .zshrc

```
export PATH=./vendor/bin:$PATH
```

### Initial installation from skeleton

#### Do not follow these steps unless this is the very initial setup from the four-drupal-skeleton. Instead, skip straight to "Initial site set up"

- `composer install` from your Drupal root folder
- `pnpm install`
- Copy `web/sites/example.settings.local.php` to `web/sites/default/settings.local.php` and configure `$database` object at the bottom of the file. Set this up with the information you intend to use, and then create the database locally using Sequel Ace, or preferred database management software.
- Run `drush si --existing-config && drush ib` and follow the steps, typically accepting all the default answers provided. This will import the local config and set up your database.
- If you are hitting an error that looks like the following:

```
In install.core.inc line 1000:

  Resolve all issues below to continue the installation... Access denied for user...
```

- The simplest solution for this is to create a database user with the username/password drupal/drupal and grant them full permissions to your local database.
- Save the username and password provided in the terminal by the installation process to 1Password

- Run `php web/core/scripts/drupal generate-theme --starterkit four_starterkit my_new_theme --path themes/custom`, replacing my_new_theme with the your desired theme name

### Initial site set up

- If you did NOT run through "Initial installation from skeleton":

      - Run `composer install` from your Drupal root folder
      - Copy `web/sites/example.settings.local.php` to `web/sites/default/settings.local.php` and configure `$database` object at the bottom of the file. You should be able to source a copy of a database for this site to set up locally and point at.

- Set the site up in Laravel Valet, and point the Document root to the `web` directory
- `cd web/themes/custom/my_new_theme`
- `nvm use`
- `pnpm i`
- `pnpm dev` to build a dev version, start up a dev server and watch files
- You can also enable theme scripts to be run from the project root by adding your theme to the packages in package.json along with the appripriate build and dev scripts
- Navigate to /admin/appearance and `Install and set as default` on the theme you have created

### Development settings

Since Drupal 10.1 there are now settings for Degugging Twig and bypassing the cache, these can be enabled as follows

- `drush state:set twig_debug TRUE`. Enable the Twig debug mode
- `drush state:set twig_autoreload TRUE`. Enable Twig auto reload
- `drush state:set twig_cache_disable TRUE`. Disable the Twig, page, render and dynamic page caches
- `drush state:set disable_rendered_output_cache_bins TRUE`. Disables render cache, dynamic page cache, and page cache.

```
drush state:set twig_debug TRUE
drush state:set twig_autoreload TRUE
drush state:set twig_cache_disable TRUE
drush state:set disable_rendered_output_cache_bins TRUE
```

These settings will not be included in any config exports.

### Vscode

To insure the best experience while developing ensure you have all the recommended extensions installed. You can do this when the project is opened by accepting any recommendations that popup or search `@recommended` in extensions and install any extensions you don't have in the Workspace Recommendations section.

---

# Styling

### Components

Any sass or js files added in the components folders will generate their own files. These will then need to be declared in the libaries.yml. If added to the base config they will be included in the main bundle. If You wish to code split so that the css and js is only loaded when needed, create a seperate config i.e

```
paragraph-accordion:
  version: 1.0
  css:
    component:
      css/components/paragraph-accordion.css:
        weight: -10
  js:
    js/components/paragraph-accordion.js: {}

```

and attach it in your twig template

```
{{ attach_library('four/paragraph-accordion') }}
```

You can bypass generating a seperate file by prefixing the file name with an `_`, and then import in either `styles.scss` or `site.js`

### Breakpoints

https://github.com/sass-mq/sass-mq

Set your breakpoints in `sass/init/_mq-settings.scss`

##### Usage

```
@use '~sass-mq/mq' as *;

.root {
  @include mq($from: tablet) {
    // Breakpoint styles
  }
}
```

### sass-rem

Where possible you should be using rem rather than px which can be easily using the sass-rem mixin

https://github.com/pierreburel/sass-rem

##### Usage

```
@use '~sass-rem' as to;

.root {
  font-size: to.rem(18px);
}
```

### CSS Variables

Where possible use css variables, some base variable have been include already here `sass/init/_root.scss`

There are exceptions to this for example if you have a mixin that requires a varible, these should be set in `styles/base/_variables.scss` and these can then be passed to a css variable if needed.

For example the min and max page width are set here

```
$default-min-bp: 320px;
$default-max-bp: 1245px;
```

### Mixins

Mixins can be found in `sass/init/_mixins.scss` and includes the following helpers

#### fluid

The fluid allows to scale fonts and other pixel based values such as margins and padding based of the screen width

##### Usage

```
@use '../init/mixins' as *;

.root {
  font-size: fluid(18px, 60px);
}
```

#### contentWithGutters

This mixin should be used on any content that displays paragraphs. This provide 3 possible areas where content can be displayed:

- content - this is used for display content in the center which is restricted to a maximum character width for accessiblity. This can be controlled by setting the `--max-text-width` value in the `:root`
- popout - allows for displaying content that need to be wider but only to a max width difined by `--page-width`
- full - This is for content that need to be displayed full width with no page gutter. Content within this will have the same rules applied as above.

By default paragraphs will be placed in the popout area, and for the content area a `.narrow` class and for full area use the `.fullwidth` class.

Alternatively you can use grid-column i.e

```
.paragraph--my-paragraph {
  grid-column: content;
}
.paragraph--my-paragraph {
  grid-column: popout;
}
.paragraph--my-paragraph {
  grid-column: full;
}
```

#### contentGrid

This implements a grid system that doesn't require any breakpoints and allow you to define the max number of columns and the minimum width of the items with the grid i.e

```
.grid {
  --grid-column-count: 3;
  --grid-item--min-width: #{to.rem(200px)};
  @include contentGrid;
}
```

---

# Deployment

In most cases use the AWS stack https://bitbucket.org/rckt/four-aws-cdk-drupal-stack

This will set up everything correctly to work with this skeleton. The only thing that will need configuring is the bitbucket pipeline which will need the following environment variables

#### Respository Variables

```
AWS_ACCESS_KEY_ID // This will be displayed in cdk deployment output
AWS_SECRET_ACCESS_KEY // This will be displayed in cdk deployment output
AWS_DEFAULT_REGION // Usually eu-west-2
APPLICATION_NAME // This is the Elastic Beanstalk Application name
S3_BUCKET // This is the Elastic Beanstalk S3 bucket name
SENTRY_DSN // This is provided after creating a php project in Sentry
THEME_NAME // i.e four_starterkit
```

#### Deployment Variables (staging / production)

```
ENVIRONMENT_NAME // This is the Elastic Beanstalk Environment name
SENTRY_ENVIRONMENT // staging or production
```

### Import/Export config and content?

After making any changes to the Drupal config the changes should be exported so that they can be applied to deployed sites.

To export your config and content run the following drush commands

```
drush cex
```

To import the lastest config run the following drush commands

```
cd /var/app/current
drush cim
drush cr
```

If you have any structured data i.e custom blocks that you'd like to add to config this can be done using the Structured Sync Module. **PLEASE NOTE when importing things such as taxonomies you should you the safe mode, otherwise any items not in config will be removed**

https://www.drupal.org/project/structure_sync

The available Drush commands are:

- export-taxonomies (et) - Export taxonomy terms to configuration
- import-taxonomies (it) - Import taxonomy terms from configuration
- export-blocks (eb) - Export custom blocks to configuration
- import-blocks (ib) - Import custom blocks from configuration
- export-menus (em) - Export menu links to configuration
- import-menus (im) - Import menu links from configuration
- export-all (ea) - Export taxonomy terms, custom blocks and menu links to configuration
- import-all (ia) - Import taxonomy terms, custom blocks and menu links from configuration

---

# Update drupal core

```
composer update drupal/core "drupal/core-*" --with-all-dependencies
drush updb
drush cr
```

# Update drupal modules

```
composer update drupal/modulename --with-dependencies
drush updb
drush cr
```
