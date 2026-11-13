# Street map

Figma T13 "Section · The street": an optional centred heading, the Brand
Category filter bar, then the street map full width (640px tall on desktop,
440px on mobile).

The `map` slot takes the `<pimlico-map>` element, built by the `pimlico_map`
module (`MapBuilder::overview()`); the paragraph template passes it in from
`includes/paragraph.inc`. The filter buttons set the element's `category`
attribute, so only that category's tenancies stay yellow and the brand
search follows it. Category names must match the feed's (the top-level
Brand Category terms).
