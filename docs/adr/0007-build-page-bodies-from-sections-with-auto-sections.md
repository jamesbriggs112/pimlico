# Build page bodies from sections, with auto sections that fill themselves

Brands and Stories were structured pages: fixed fields for the hero and details, a short list of sections, then parts printed by the page template: Similar brands at the foot of a Brand, and "Also on this street" or "Discover the places in the story" at the foot of a Story. Editors asked for more freedom: text, headings, images, galleries and video anywhere in the body, and the closing cards optional.

The body of every Brand, Article and Press article is now its sections (`field_sections`), and the closing cards are sections too. Two new Paragraph types, **Similar brands** (`similar_brands`) and **Related** (`related`), have no required fields (only an optional heading) and fill themselves from the node they're on: the editor's picks on the node, or the same fallback listing the template used before (`brands:block_similar` by category; `news:block_related` by news category; three random Brands for Press). The heroes, title bands, the Brand's "Find the showroom" band and a Story's credits stay fixed in the templates, as the client asked.

Each content type allows only the sections that make sense there (the field's target bundles):

| Content type | Sections |
|---|---|
| Brand | Introduction, Text, Image, Image gallery, Video, Quote, Info rows, Similar brands |
| Article, Press article | Text, Image (with its caption), Image gallery, Video, Quote, Related |
| Page | Unchanged: every page section, none of the auto sections |

## Choices within it

- **No Heading section.** The Text section (`text_wysiwyg_`) already has an optional Heading field, and its editor offers Heading 2 and Heading 3, so a heading on its own is a Text section with only the Heading filled in. A separate type would print the same `<h2>` with a second name for it. The type is labelled "Text" to editors (it was "Rich text"), with a description saying so.
- **The picks stay on the node.** `field_similar_brands` and `field_related` keep their place on the Brand and Story forms; the auto section reads them. Moving them into the section would have needed two section types for Related (Stories for News, Brands for Press, as the field targets differ), a data migration of every pick, and would have lost the picks when an editor removed the section. Kept on the node, they're unchanged for editors and for anything else that reads them.
- **The section reads its host in preprocess.** The section knows its node only through `getParentEntity()`. The theme's `includes/auto_sections.inc` gets it there, renders the picks with the node's own display (`field->view('full')`, so field access and the cards' cache tags come with them) and adds the node as a cache dependency. Twig never reaches through to the node. A section that isn't on the right kind of node prints nothing.
- **A closing auto section follows the fixed parts.** The design puts the cards after "Find the showroom" and after the credits. So a run of auto sections at the end of the list prints there (`_pimlico_closing_sections()`); moved further up, the section prints where it was put. Every existing page therefore looks as it did.
- **New Brands and Stories start with theirs.** `pimlico_sections` adds the auto section on a node's first save (`hook_node_presave`), at the end, so sections added on the add form stay above it, and the import and seed scripts get it too. It isn't the Paragraphs widget's "default paragraph type", which would put it first on the form and above every section an editor adds. `AutoSections::TYPES` is the one list of which section fills itself on which content type.
- **Existing pages get theirs in a deploy hook.** `pimlico_admin_deploy_auto_sections` appends the section to every Brand and Story without one. It is a deploy hook, not a post-update, because the section types arrive in this release's configuration, which `drush deploy` imports after post-updates. It is re-runnable.

## Consequences

- Editors can remove or move Similar brands and Related, or change their heading. An editor who removes one leaves the picks on the node unused; the field help says the section shows them.
- A new content type with self-filling cards adds its type to `AutoSections::TYPES`, a preprocess for its host and, if the cards close the page, a `_pimlico_closing_sections()` call for its full view.
- Reusable sections (the Paragraphs library) were planned alongside this but need `drupal/entity_usage`, a new Composer dependency of `paragraphs_library`; they are a separate decision.
