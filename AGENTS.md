# Agent instructions for Pimlico Road

- Dev site url is https://pimlico.test (Laravel Valet, PHP 8.4). See `docs/local-development.md`.
- In development the theme is using pnpm dev so assume this is already running there is never any need to run pnpm build or pnpm dev
- Always use css vars over sass vars
- Never modify contributed modules
- This is NOT using DDEV
- After making config changes make sure you run `drush cim`
- After making significant changes make sure you run `drush cr`
- Check whether a component exists before adding HTML and extra classes
- Before rewriting templates, inspect the rendered output and the configuration driving it. Preserve existing rendering behaviour when styling changes are sufficient.
- For Drupal presentation work, use existing rendered fields, display configuration, Twig Tweak, and theme components first. Before adding preprocess PHP, identify the exact requirement those mechanisms cannot satisfy and verify that limitation against the installed code. Do not manually reproduce field rendering, access checks, translation, cache metadata, or breadcrumbs already handled by Drupal. Prefer deleting custom code over defending it with more tests.

## Agent skills

### Code quality

For every change, and for any review or refactor, follow `docs/agents/code-quality.md`: take a baseline first, keep code DRY and well named, let Drupal render and cache, and prove the result with `scripts/qa/`.

### Code review

When reviewing pull requests, follow `docs/agents/code-review.md`.

### Issue tracker

Issues are tracked in GitHub Issues for `Four-Agency/pimlico-website`.

### Domain docs

`CONTEXT.md` is the glossary of domain terms: use its words, not the synonyms it lists under _Avoid_. `docs/adr/` records the architecture decisions; read the ones that touch your work.

### Architecture and plan

The technical architecture is in `docs/TAD.md` and the delivery plan in `docs/build-plan.md`. Follow the naming conventions in TAD Appendix A.
