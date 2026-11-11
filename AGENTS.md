# Agent Instructions for Four Drupal Skeleton

- Dev site url is https://drupal-skeleton.test
- In development the theme is using pnpm dev so assume this is already running there is never any need to run pnpm build or pnpm dev
- Always use css vars over sass vars
- Never modify contributed modules
- This is NOT using DDEV
- After making config changes make sure you run `drush cim`
- After making significant cahnge make sure you run `drush cr`
- Check if a component exist for the code you are adding blindy adding html an additional classes
- Before rewriting templates, inspect the rendered output and the configuration driving it. Preserve existing rendering behaviour when styling changes are sufficient.
- For Drupal presentation work, use existing rendered fields, display configuration, Twig Tweak, and theme components first. Before adding preprocess PHP, identify the exact requirement those mechanisms cannot satisfy and verify that limitation against the installed code. Do not manually reproduce field rendering, access checks, translation, cache metadata, or breadcrumbs already handled by Drupal. Prefer deleting custom code over defending it with more tests.
