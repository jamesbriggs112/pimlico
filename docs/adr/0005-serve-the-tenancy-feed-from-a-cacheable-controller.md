# Serve the Tenancy Feed from a cacheable controller

The Tenancy Feed is served at `/api/map/brands` by a controller returning a `CacheableJsonResponse` in exactly the shape the map component reads. It includes only published, active Brands shown on the map, carries the Brands' and Units' list cache tags, and is cached by the page cache and CloudFront until an editor saves a Brand or Unit.

A Views REST export was rejected because the Brand to Unit to Map Building join yields one row per Map Building, which would then have to be merged in the browser or in a custom style plugin. JSON:API was rejected because the browser would have to perform the join and the component would need to understand Drupal's entity structure. Inlining the feed in every page was kept as an option for later, but the shared endpoint is cached once for all pages.

## Consequences

- The feed's shape is a contract with the map component, asserted by a Kernel test.
- The feed is public, read-only data, so it needs no access control beyond the published and status conditions.
