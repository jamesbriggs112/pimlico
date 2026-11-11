# Hero film

Figma "Hero · Street film" (Home) and "Hero · Yard film" (Newson’s Yard): a full-bleed silent film under a dark gradient, the page heading (`h1`) and a 48px media control labelled "Pause film".

- The header switches to its transparent version over it (`page.html.twig` checks the first section).
- The film comes from the media `background` view mode: autoplay, muted, looping, inline, no controls.
- The poster image sits underneath and is all that shows for people who prefer reduced motion, until they press play.
- Pausing is required by WCAG 2.2.2 for moving content over five seconds.
