# Build every visual pattern as a Single Directory Component

Every visual pattern in the design is a Single Directory Component in the `pimlico` theme, scaffolded with `drush generate four-sdc`, documented with a Storybook story and named after the Figma component it implements. Drupal templates are thin adapters that map fields onto components with `include` or `embed` and `only`. Paragraph types share the name of the component they render.

Styling Drupal's own templates directly was rejected because markup and styles would then be spread across field, node, paragraph and views templates, with no single place to see or test a pattern. This follows the reference project and the skeleton's starterkit.

## Consequences

- A pattern used by both a paragraph and a node (for example the split hero) is one component with two thin adapters.
- Kernel theme tests can render a component in isolation and assert its markup.
- Figma components that the site doesn't use (Event card, Event row, Toggle, Dialog) are not built.
