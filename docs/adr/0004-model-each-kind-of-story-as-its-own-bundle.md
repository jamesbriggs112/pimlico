# Model each kind of Story as its own bundle

Articles, Podcast Episodes and Press Articles are three node bundles (`article`, `podcast_episode`, `press_article`) sharing field storage for everything they have in common: banner, teaser, News Category, sections, related content and the SEO set. The News listing queries all three.

A single bundle with a type switch was rejected because each kind has fields the others don't (audio, episode number and transcript; Publication, logo and source link), and conditional fields make the edit form harder to use and the templates harder to read. Separate bundles give each its own form display, full view template and Schema.org type.

## Consequences

- Shared fields reuse one storage each, following the project's `field_` naming without bundle prefixes.
- A fourth kind of Story is a new bundle, plus adding it to the News view's bundle filter.
