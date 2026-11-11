# Launch checklist

Phase 7 of the build plan. Automated checks were run locally on 11 November 2026; the rest needs a person, a device or a client decision.

## Done and verified locally

| Check | Result |
|---|---|
| Security advisories (`composer audit`) | None. Updated Drupal core 11.4.7 → 11.4.12 (one critical remote code execution, two XSS) and CAPTCHA, Easy Breadcrumb, Gin, Linked Field, Webform, SVG Image and Symfony packages. `drush updatedb` run. |
| Accessibility: axe, WCAG 2.2 AA | No violations on 17 templates at 1440 and 390. Fixed: pumpkin text was 3:1 (now `--color-brand-pumpkin-text` #a64f00, 5:1); yellow eyebrows on dark bands lost to the global eyebrow colour; info rows were invalid `<dl>` markup. |
| Keyboard | Skip link first; every stop on Home, Contact and a Brand page has a visible focus style. |
| 200% zoom (720px) and 390px | No sideways scroll on any template. Fixed: visually hidden text in the gallery and card swipe tracks widened Brand pages to 2,000px+. |
| Reduced motion | The hero film never starts; Play is available. |
| Performance (mobile, slow 4G, 4× CPU, aggregation on, local Valet) | LCP: Home 2.6 s, Brand 0.9 s, Visit 0.7 s, News 0.8 s. CLS 0 everywhere. Fixed: above-the-fold images were lazy-loaded (new "Hero" media view mode loads them eagerly); the hero film downloaded before anything else (now `preload="none"`, starts after page load, skipped on data saver or slow connections). |
| Map | `scripts/map-qa.sh`: 96/96. |
| Tests and standards | PHPUnit (3 kernel tests), phpcs, ESLint, Prettier clean. |

## Needs a decision or content before launch

- [ ] **Hero film budget.** The film uploaded on 11 November (`pimlicoPlaceholder.mp4`) is 92.8 MB. Aim for a 10–20 s loop at 3–5 MB (H.264, 1080p, no audio), ideally with a 720p version for phones. It now waits until the page has loaded, but on 4G it still uses a lot of data.
- [ ] **Sentry in the browser.** The Raven module ships only the tracing build (145 KB) of the browser SDK. Keep browser error reporting (as now), or turn off "JavaScript error handler" in Raven settings to save the weight.
- [ ] **CookieScript and Google Tag** (build plan 6.4): waits on the CookieScript account.
- [ ] **Redirects** from belgraviavillage.com Pimlico Road URLs: needs the list of old URLs.
- [ ] **Brand data:** six brands have no postcode, Food & Drink brands need their sub-category, and the flagged map placements (map guide B7) need checking with the client in the Unit map picker.
- [ ] **Real copy and photography** for the pages built by `scripts/dev/seed-pages.php`, and Brand images, galleries and about text.
- [ ] Design queries listed under phases 3 to 6 in `docs/build-plan.md`.

## Needs a person or a device (7.1, 7.2)

Record results here.

| Test | Who | Result |
|---|---|---|
| VoiceOver, macOS Safari: Home, Brand (map), Visit (map, filter, search), Contact form errors | | |
| VoiceOver, iOS Safari: menu, map full screen overlay, gallery swipe | | |
| Safari macOS: map trackpad pinch, full screen | | |
| iOS Safari: map pinch and the full screen overlay, hero film | | |
| Android Chrome: map, menu, forms | | |
| Firefox: all templates | | |
| Map keyboard use on Brand pages: every lit building is a tab stop (about 40) before "Similar brands". Raise with the map project (a single tab stop with arrow keys inside would be kinder). | Map project | Logged |

## Environment set-up (7.5)

Set these as Elastic Beanstalk environment properties for staging and production:

| Property | Value |
|---|---|
| `ENVIRONMENT` | `staging` or `production` (loads `config/<env>/<env>.config.php`) |
| `SITE_HOST` | The public host name, e.g. `www.example.com`. It's added to `trusted_host_patterns` (the skeleton only trusts `*.preflight.site` and `10.0.x.x`) and used as the sitemap base URL. |
| `HASH_SALT` | A long random string, different per environment. |
| `SENTRY_DSN`, `SENTRY_ENVIRONMENT`, `SENTRY_RELEASE` | From Sentry (placeholders in `.ebextensions/sentry.config`). |
| `SMTP_USERNAME`, `SMTP_PASSWORD` | Mail credentials. |

## First deploy to staging

The post-deploy hook now runs `drush deploy` (updates, config import, cache rebuild). The skeleton's hook never imported config. Then, once:

1. `drush migrate:import --tag=pimlico` (47 Brands and their Units).
2. `drush php:script scripts/dev/seed-pages.php` (the site pages; it also sets the front page). Upload real photography afterwards.
3. `drush --uri=https://$SITE_HOST simple-sitemap:generate`.
4. Check the map at `/visit` and on a Brand page, and `/api/map/brands`.
5. Staging stays `noindex` (`config/staging/staging.config.php`).

## Go-live

- [ ] Staging sign-off by the client.
- [ ] DNS and TLS for the production domain; `SITE_HOST` set.
- [ ] CloudFront in front, and the feed purge working: save a Brand on production and check `/api/map/brands` updates.
- [ ] Sentry receiving errors from production.
- [ ] Uptime monitoring on `/` and `/api/map/brands`.
- [ ] Submit the sitemap in Google Search Console.
- [ ] Uninstall `pimlico_migrate` once the import is final.
