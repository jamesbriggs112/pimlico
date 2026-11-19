# Street map

Figma T13 "Section · The street": an optional centred heading, the Brand
Category filter bar, then the street map full width (640px tall on desktop,
440px on mobile).

The `map` slot takes the `<pimlico-map>` element, built by the `pimlico_map`
module (`MapBuilder::overview()`); the paragraph template passes it in from
`includes/paragraph.inc`. The filter buttons set the element's `category`
attribute, so only that category's tenancies stay yellow and the brand
search follows it.

The categories are the listings' filters, in order: the Brands page
filters, then the Dine filters (`BrandGroups::mapCategories()`), so "Food &
Drink" is never one bucket. Their names must match the feed's `category`
(`BrandGroups::mapCategoryFor()`), which is how the map matches them.

Every tenancy is the same brand yellow (`--pm-active`), as in Figma (Visit,
23:950 and 22:642): the design has no per-category colours or legend, and
the map component (ADR 0001) colours tenancies by filter, not by category.
