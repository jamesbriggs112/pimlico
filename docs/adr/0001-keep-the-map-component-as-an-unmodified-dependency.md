# Keep the map component as an unmodified dependency

The Street Map is drawn by the `<pimlico-map>` web component and its Base Map file, both built and tested in the separate `pimlico-map` project. Drupal copies the two built files (`pimlico-map.js` and `pimlico-map.json`) into the `pimlico_map` module unchanged and versions them in the library definition. Fixes and features are made in the map project, tested with its browser suite, and copied in again; the copies in this repository are never edited.

Rewriting the component as an SDC or as Drupal behaviours was rejected because it would fork tested code and lose the map project's test suite. Editing the copies in place was rejected because the next copy would silently overwrite those edits.

## Consequences

- The theme styles the map only through the component's CSS custom properties and `::part()` selectors, and wraps it in the `street_map` SDC for sizing, heading and filter bar.
- Bumping the library `version` is part of every copy, so browsers don't keep an old component.
- Rebuilding the Base Map can change Map Building identifiers. ADR-0002 keeps that change away from Brands.
