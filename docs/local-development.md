# Local development

The site runs on **Laravel Valet** at **https://pimlico.test**, like the rest of the Four team's Drupal sites (not DDEV). PHP 8.4 matches the AWS servers (`.ebextensions/packages.config`).

## One-off set-up

You need Homebrew, PHP 8.4, Composer, MySQL, Node 22, pnpm and admin rights on your Mac (Valet uses `sudo` to install).

```bash
brew install php@8.4 composer mysql
brew services start mysql
npm i -g pnpm@10
composer global require laravel/valet
```

Add Composer's global tools to your PATH in `~/.zshrc` (make sure the file ends with a newline first):

```bash
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

Then install Valet, switch it to PHP 8.4, and link and secure the site from `web/`:

```bash
valet install
valet use php@8.4
cd web && valet link pimlico && valet secure pimlico
```

Then install the site:

1. Run `composer install` from the project root.
2. Copy `web/sites/example.settings.local.php` to `web/sites/default/settings.local.php` and set the `$databases` array at the bottom to your local MySQL database (create it first, for example in Sequel Ace). If the installer says "Access denied", create a MySQL user `drupal` with password `drupal` and full rights on that database.
3. Run `vendor/bin/drush si --existing-config`, then `vendor/bin/drush php:script scripts/dev/seed-pages.php` and `vendor/bin/drush php:script scripts/dev/seed-brand-content.php` for sample content.
4. In `web/themes/custom/pimlico`, run `pnpm i` and `pnpm build` (or `pnpm dev` to watch).
5. Log in with `vendor/bin/drush uli`.

For Twig debugging and no caching while developing (never exported with config):

```bash
vendor/bin/drush state:set twig_debug TRUE
vendor/bin/drush state:set twig_autoreload TRUE
vendor/bin/drush state:set twig_cache_disable TRUE
vendor/bin/drush state:set disable_rendered_output_cache_bins TRUE
```

How the theme is written (BEM, tokens, breakpoints, mixins, tests) is in [front-end-conventions.md](front-end-conventions.md).

Add the local hosts to the end of `settings.local.php`:

```php
$settings['trusted_host_patterns'][] = '^pimlico\.test$';
$settings['trusted_host_patterns'][] = '^localhost$';
$settings['trusted_host_patterns'][] = '^127\.0\.0\.1$';
```

### If `pimlico.test` doesn't resolve

Valet sends every `*.test` address to `127.0.0.1` through dnsmasq. On Macs running the company's Sophos ZTNA client, that DNS rule can be bypassed (`ping pimlico.test` says "Unknown host"). Add the site to `/etc/hosts` instead:

```bash
echo "127.0.0.1 pimlico.test" | sudo tee -a /etc/hosts
```

### Storybook assets

The skeleton README's nginx rule (static assets with `Access-Control-Allow-Origin *`) is added to `~/.config/valet/Nginx/pimlico.test` in both server blocks. Re-add it if you run `valet secure` again, which rewrites that file, then run `valet restart nginx`.

## Every day

The theme (styles and scripts, with hot reload):

```bash
cd web/themes/custom/pimlico && pnpm dev
```

Open https://pimlico.test. If the page has no styling, `pnpm dev` isn't running. To sign in:

```bash
vendor/bin/drush uli --uri=https://pimlico.test
```

Valet sets PHP's memory limit to 512MB. For a full `drush si` or a large `drush cim`, lift it: `php -d memory_limit=-1 vendor/bin/drush ...`.

## Storybook

Stories live beside each component (`*.stories.twig`) and are compiled to JSON by Drush:

```bash
vendor/bin/drush storybook:generate-all-stories --uri=https://pimlico.test
```

Then, with `pnpm dev` running:

```bash
cd web/themes/custom/pimlico && pnpm storybook
```

Storybook opens at http://localhost:6006. Rendering works without extra permissions because `development.services.yml` turns on Storybook's development mode.

Vite serves assets from `http://localhost:5173` (its `server.origin`). Chrome and Firefox allow those on the HTTPS site because `localhost` counts as secure. Set `VITE_DEV_ORIGIN` if your dev server runs elsewhere.

## Without Valet

On a Mac without admin rights, `scripts/serve.sh` serves the site on PHP's built-in server at http://localhost:8888. It has no HTTPS (so the map's "show my location" can't be tested) and handles one request at a time.
