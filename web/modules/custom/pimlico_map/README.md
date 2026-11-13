# Pimlico map

The street map: Units, the Tenancy Feed, the map on Brand pages and the Unit
map picker. The map component itself is the map project's
(`pimlico-map`), copied in unchanged (docs/adr/0001).

## Updating the map

1. In the map project, rebuild and test (`./run.sh`, `npm test`) and tag a
   release.
2. Copy `map/pimlico-map.js` and `output/map/pimlico-map.json` into
   `assets/`.
3. Bump `version` in `pimlico_map.libraries.yml`. It is also the base map's
   `?v=`, which is what gets the new map past CloudFront and the one-year
   expiry.
4. Run `scripts/map-qa.sh` from the project root.

If the base map's building IDs change, re-point the affected Units with the
picker (Content » Units).

## Pieces

- `Entity/Unit`: a shopfront Brands trade from (`field_units` on Brand).
- `BrandFeed` and `Controller/BrandFeedController`: `/api/map/brands`.
- `MapBuilder`: render arrays for the `<pimlico-map>` element (overview,
  brand, picker). The theme's `street_map` component and Brand template
  place them.
- `Hook/MapHooks`: theme hook and the Brand "Showroom map" pseudo-field.
- `Hook/CacheInvalidationHooks`: purges the feed from CloudFront.
- `Plugin/Field/FieldWidget/MapBuildingPickerWidget` and
  `js/pimlico-map-picker.js`: the Unit picker.
