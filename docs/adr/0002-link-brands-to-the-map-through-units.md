# Link Brands to the map through Units

A Brand references the Units it trades from, and each Unit holds the Map Buildings it occupies. A Brand never stores map geometry or Map Building identifiers itself. The Tenancy Feed joins Brand to Unit to Map Building in one place.

Storing Map Buildings directly on the Brand was rejected for three reasons. When a Brand leaves, its place on the map would be lost rather than kept for the next occupant. Vacant shopfronts could not be listed. And when the Base Map is rebuilt and Map Building identifiers change, every affected Brand would need editing, rather than only the Units.

## Consequences

- Editors create or choose a Unit before a new Brand can light the map.
- A Brand with no Units is valid content but is absent from the Tenancy Feed.
- Re-pointing Units after a Base Map rebuild is a developer task with the map picker, not an editorial one.
