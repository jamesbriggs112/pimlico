# Pimlico Road

This context describes the brands, places and stories presented by the Pimlico Road website, and the street map that ties brands to the buildings they trade from.

## Language

**Brand**:
A business trading on Pimlico Road, a neighbouring street, or in Newson's Yard, which has its own page on the site. Restaurants and cafés are Brands in the Food & Drink Brand Category.
_Avoid_: Shop, Tenant, Retailer

**Brand Category**:
The single primary grouping a Brand is filtered by, on listings and on the map: Home & Interiors, Antiques and art, Soft furnishings and fabrics, Lighting, or Food & Drink. Categories are ordered by weight.
_Avoid_: Tag, Sector

**Specialism**:
A short line describing something a Brand offers, shown under "What you'll find" on its page. Specialisms are free text per Brand, not a shared vocabulary.
_Avoid_: Service, Brand Category

**Similar Brands**:
Up to three Brands an editor chooses to show at the foot of a Brand page. When none are chosen, three other Brands in the same Brand Category are shown.
_Avoid_: Related brands

**Unit**:
A physical shopfront that Brands trade from, such as "95–97 Pimlico Road". A Unit points at one or more Map Buildings and outlives the Brands that occupy it, so a Brand leaving never loses the Unit's place on the map. Units are editor-only records with no public page.
_Avoid_: Address, Location, Building

**Map Building**:
A building or shop-unit identifier in the Base Map, such as `w535769837` or `w535769910-76-80`. Editors choose Map Buildings by clicking the map, and never type them.
_Avoid_: Building ID, Polygon

**Base Map**:
The static geometry of the street (ground, roads, green space, every building, street names) built from OpenStreetMap by developers and shipped as a file. Editors never change it.
_Avoid_: Map data, Tiles

**Tenancy Feed**:
The list of active Brands and their Map Buildings that Drupal serves to the map. It is the only map data editors control.
_Avoid_: Brands API, Map JSON

**Tenancy Status**:
Whether a Brand lights its Units on the map: `active`, `coming_soon` or `closed`. Only an active Brand that is published and shown on the map appears in the Tenancy Feed.
_Avoid_: Published

**Street Map**:
The interactive map of the street. On the Visit page it shows every active Brand with a Brand Category filter and brand search; on a Brand page it is zoomed to that Brand.
_Avoid_: Google map, Location map

**Area**:
A named part of the neighbourhood that Units are grouped by: Pimlico Road, Newson's Yard, Ebury Street, Bourne Street and the like.
_Avoid_: Zone, District

**Newson's Yard**:
The former timber yard off Pimlico Road, reopened as a home for makers. It is an Area, and the Brands trading there are its Resident Brands.
_Avoid_: The Yard (in content), Newsons Yard

**Story**:
Any item in News: an Article, a Podcast Episode or a Press Article. Stories share a News Category and appear together in the News listing.
_Avoid_: Post, Blog

**Press Article**:
A Story that summarises coverage of Pimlico Road in another Publication and links to the original source.
_Avoid_: Press release

**Publication**:
The newspaper, magazine or site that published the coverage a Press Article refers to.
_Avoid_: Source, Outlet
