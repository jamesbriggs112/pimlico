# Model Units as a custom content entity

Units are a custom content entity, `pimlico_unit`, defined by the `pimlico_map` module, with fields and an admin listing but no canonical route. A Unit is an editor-only record that visitors never see.

A `unit` node type was rejected because nodes bring public pages, URL aliases, sitemap entries, metatags and search indexing that would all need suppressing for something no visitor should reach. A taxonomy vocabulary was rejected because term pages are public by default and the term overview is a poor way to manage a register of shopfronts with addresses and map positions.

## Consequences

- The module owns the entity class, its access handler, list builder and form, and must ship Kernel tests for them.
- Views can still list Units (for vacancy reports) through the entity's views data.
