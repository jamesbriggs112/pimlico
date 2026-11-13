# Launch checklist

Phase 7 of the build plan. Automated checks were run locally on 11 November 2026; the rest needs a person, a device or a client decision.

## Done and verified locally

| Check | Result |
|---|---|
| Security advisories (`composer audit`) | None. Updated Drupal core 11.4.7 → 11.4.12 (one critical remote code execution, two XSS) and CAPTCHA, Easy Breadcrumb, Gin, Linked Field, Webform, SVG Image and Symfony packages. `drush updatedb` run. |
| Accessibility: axe, WCAG 2.2 AA | No violations on 17 templates at 1440 and 390, except eyebrow colour: the client chose the design orange #e87400 (3:1 on white, below AA for small text) over the AA-passing #a64f00. Fixed: yellow eyebrows on dark bands lost to the global eyebrow colour; info rows were invalid `<dl>` markup. |
| Keyboard | Skip link first; every stop on Home, Contact and a Brand page has a visible focus style. |
| 200% zoom (720px) and 390px | No sideways scroll on any template. Fixed: visually hidden text in the gallery and card swipe tracks widened Brand pages to 2,000px+. |
| Reduced motion | The hero film never starts; Play is available. |
| Performance (mobile, slow 4G, 4× CPU, aggregation on, local Valet) | LCP: Home 2.6 s, Brand 0.9 s, Visit 0.7 s, News 0.8 s. CLS 0 everywhere. Fixed: above-the-fold images were lazy-loaded (new "Hero" media view mode loads them eagerly); the hero film downloaded before anything else (now `preload="none"`, starts after page load, skipped on data saver or slow connections). |
| Map | `scripts/map-qa.sh`: 96/96. |
| Tests and standards | PHPUnit (5 kernel tests), phpcs, ESLint, Prettier clean. |

## Needs a decision or content before launch

- [ ] **Hero film budget** (client handling it). The film uploaded on 11 November (`pimlicoPlaceholder.mp4`) is 92.8 MB. Aim for a 10–20 s loop at 3–5 MB (H.264, 1080p, no audio), ideally with a 720p version for phones. It now waits until the page has loaded, but on 4G it still uses a lot of data.
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

Set the Elastic Beanstalk environment properties and the GitHub Actions variables and secrets for staging and production, as listed under [Environments](TAD.md#environments) in the TAD. The Sentry values are GitHub variables: each deploy writes them into `.ebextensions/sentry.config`.

## First deploy to staging

The post-deploy hook now runs `drush deploy` (updates, config import, cache rebuild). The skeleton's hook never imported config. Then, once:

1. Import the Structure Sync content, which `drush deploy` doesn't: `drush it --choice=safe`, `drush im --choice=safe` and `drush ib --choice=safe` (taxonomy terms, menu links, custom blocks). The import in the next step looks up Brand categories and Areas by name.
2. `drush migrate:import --tag=pimlico` (47 Brands and their Units).
3. Create the site pages. `scripts/dev/seed-pages.php` builds them and sets the front page, but the deploy zip (`.github/workflows/deploy.yml`) doesn't include `scripts/`, so it isn't on the server. Either add `scripts` to the zip for the first deploy and run `drush php:script scripts/dev/seed-pages.php`, or create the pages by hand and set the front page under Configuration » Basic site settings. Upload real photography afterwards.
   Note that `config/sync/system.site.yml` sets the front page to `/node/113`, and `drush deploy` imports it on every deploy, which undoes either way of setting it unless Home is node 113. Until that config is changed (for example to `/home`, Home's alias), check the front page after each deploy.
4. `drush --uri=https://$SITE_HOST simple-sitemap:generate`.
5. Check the map at `/visit` and on a Brand page, and `/api/map/brands`.
6. Staging stays `noindex` (`config/staging/staging.config.php`).

## Go-live

- [ ] Staging sign-off by the client.
- [ ] DNS and TLS for the production domain; `SITE_HOST` set.
- [ ] CloudFront in front, and the feed purge working: save a Brand on production and check `/api/map/brands` updates.
- [ ] Sentry receiving errors from production.
- [ ] Uptime monitoring on `/` and `/api/map/brands`.
- [ ] Submit the sitemap in Google Search Console.
- [ ] Uninstall `pimlico_migrate` once the import is final.
