# Hero film

Figma "Hero · Street film" (Home) and "Hero · Yard film" (Newson's Yard): a full-bleed silent film under a dark gradient, the page heading (`h1`) bottom left and a 48px media control labelled "Pause film" bottom right (under the heading, right-aligned, on mobile).

- The header switches to its transparent version over it (`page.html.twig` checks the first section).
- The film comes from the media `background` view mode: autoplay, muted, looping, inline, no controls.
- The poster image sits underneath and is all that shows for people who prefer reduced motion, until they press play.
- Pausing is required by WCAG 2.2.2 for moving content over five seconds.
- The film is optional. With only an image (the paragraph's Video left empty) the hero shows the image and no Pause film control.
