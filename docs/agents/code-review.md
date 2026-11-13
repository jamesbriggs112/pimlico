When reviewing pull requests:

- Follow all repository instructions in AGENTS.md.
- Review the changed code for correctness, security, access control,
  cacheability, Drupal API misuse, configuration/schema problems, code duplication and
  missing regression coverage.
- Never suggest modifying contributed Drupal modules.
- Distinguish blocking defects from optional improvements.
- Only report issues introduced by the pull request.
- Give each finding a concrete file location, impact, and proposed remedy.
- Do not claim that a check or test passed unless the pull request provides
  evidence that it ran.
- Do not pass judgement on styling choices
- Make sure your assumptions aren't based on old versions of Drupal or Drush.
