# Model each kind of Story as its own bundle

Articles, Podcast Episodes and Press Articles are three node bundles (`article`, `podcast_episode`, `press_article`) sharing field storage for everything they have in common: banner, teaser, News Category, sections, related content and the SEO set. The News listing queries all three.

A single bundle with a type switch was rejected because each kind has fields the others don't (audio, episode number and transcript; Publication, logo and source link), and conditional fields make the edit form harder to use and the templates harder to read. Separate bundles give each its own form display, full view template and Schema.org type.

## Consequences

- Shared fields reuse one storage each, following the project's `field_` naming without bundle prefixes.
- A fourth kind of Story is a new bundle, plus adding it to the News view's bundle filter.

**Update (2026-11-13):** The shared banner and teaser fields were renamed in phase 4: Stories now share `field_image` and `field_summary`. The decision is unchanged.

**Update (2026-11-19):** Podcast Episodes were dropped from the build ("we do not require it at this stage"), so the `podcast_episode` bundle, its fields and its components are gone; git history keeps them. Stories are now Articles and Press Articles, and the decision stands for those two. A fourth kind of Story also needs its label in `_pimlico_story_types()` (theme `includes/node.inc`), which feeds the News filter and the card meta.
