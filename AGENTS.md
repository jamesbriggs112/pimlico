# Agent Instructions for Pimlico Road

- Dev site url is https://pimlico.test
- In development the theme is using pnpm dev so assume this is already running there is never any need to run pnpm build or pnpm dev
- Always use css vars over sass vars
- Never modify contributed modules
- This is NOT using DDEV
- After making config changes make sure you run `drush cim`
- After making significant cahnge make sure you run `drush cr`
- Check if a component exist for the code you are adding blindy adding html an additional classes
- Before rewriting templates, inspect the rendered output and the configuration driving it. Preserve existing rendering behaviour when styling changes are sufficient.
- For Drupal presentation work, use existing rendered fields, display configuration, Twig Tweak, and theme components first. Before adding preprocess PHP, identify the exact requirement those mechanisms cannot satisfy and verify that limitation against the installed code. Do not manually reproduce field rendering, access checks, translation, cache metadata, or breadcrumbs already handled by Drupal. Prefer deleting custom code over defending it with more tests.

## Agent skills

### Code review

When reviewing pull requests, follow `docs/agents/code-review.md`.

### Issue tracker

Issues are tracked in GitHub Issues for `jamesbriggs112/pimlico`.

### Domain docs

This repository uses a single-context domain-doc layout. See `docs/agents/domain.md`.

### Architecture and plan

The technical architecture is in `docs/TAD.md` and the delivery plan in `docs/build-plan.md`. Follow the naming conventions in TAD §4.
