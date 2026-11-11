# Local development

The skeleton expects Laravel Valet, which needs admin rights to install. Until Valet is available, the site runs on PHP's built-in web server at **http://localhost:8888**. Everything except HTTPS works the same way.

## One-off set-up

You need Homebrew, PHP 8.4, Composer, MySQL, Node 22 and pnpm:

```bash
brew install php@8.4 composer mysql
brew services start mysql
npm i -g pnpm@10
```

Then follow the skeleton README's "Initial site set-up": `composer install` and `web/sites/default/settings.local.php` with the database credentials. Add the local hosts to the end of `settings.local.php`:

```php
$settings['trusted_host_patterns'][] = '^localhost$';
$settings['trusted_host_patterns'][] = '^127\.0\.0\.1$';
```

## Every day

Run these in two terminal tabs from the project root.

The site:

```bash
scripts/serve.sh
```

The theme (styles and scripts, with hot reload):

```bash
cd web/themes/custom/pimlico && pnpm dev
```

Open http://localhost:8888. If the page has no styling, `pnpm dev` isn't running.

## Drush

Drush runs with whichever `php` is first on your PATH. Use PHP 8.4 explicitly, and lift the memory limit for heavy commands such as `si` and `cim`:

```bash
/opt/homebrew/opt/php@8.4/bin/php -d memory_limit=-1 vendor/drush/drush/drush.php cr
```

To sign in, generate a one-time login link:

```bash
/opt/homebrew/opt/php@8.4/bin/php vendor/drush/drush/drush.php uli --uri=http://localhost:8888
```

## Limitations without Valet

- **No HTTPS.** The map's "show my location" button needs HTTPS, so test it on staging.
- **One request at a time.** The built-in server is single-threaded, so pages are slower than under nginx. It's not for performance testing.
- **Storybook:** the README's nginx CORS rule doesn't apply. If Storybook can't load theme assets, raise it rather than changing the theme.

## Moving to Valet later

Once you have admin rights: add `~/.composer/vendor/bin` to your PATH, then run `valet install`, `valet use php@8.4`, and from `web/`, `valet link pimlico` and `valet secure pimlico`. Then add `'^pimlico\.test$'` to the trusted hosts and change the dev URL in `AGENTS.md` back to `https://pimlico.test`.
