# Pimlico Road: design changes for the web build

Changes from design rounds 2 and 3 (client feedback of 6 Nov and 19 Nov 2026), to bring the build in line with the Figma file.

- **Figma file:** [Pimlico Visuals – V01](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01) (file key `REw9zZENN4OEozQHrt17pD`)
- **Pages in the file:** `01 Desktop` (node `2:3`), `02 Mobile` (node `2:4`), `Components` (node `2:2`)
- **Source of truth:** Figma. Where this file and Figma disagree, Figma wins. Read token values from the Figma variables, not from this file, because some have been tuned since.

## How to use this file (for Claude Code)

1. Connect the Figma MCP server and read each linked node before changing code.
2. Work top to bottom: global tokens and components first, then pages.
3. For each item, compare Figma to the current build, update the code, then tick the box in this file.
4. Ask before any structural change, such as removing a template or changing CMS fields.
5. Check every page at desktop (1440) and mobile (390).

Prompt to start:

> Read docs/design-changes.md. For each item, open the linked Figma node with the Figma MCP, compare it to the current build, update the code to match, and tick the item off in this file. Do global tokens and components first. Ask me before changing anything structural.

Link format: `https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=<id with - instead of :>`

---

## 1. Global tokens

All in the `Layout` and `Theme` variable collections. Each variable has a WEB code-syntax name set.

- [x] **Font-size scale.** Replace one-off sizes with nine tokens: `font-size/12, 14, 16, 20, 26, 32, 44, 64, 96` (CSS `--font-size-12` … `--font-size-96`). All text styles and text layers are bound to these. Old sizes that were folded in: 11→12, 18/19/22→20, 28→26, 40→44, 62/80→64.
- [x] **Text styles.** Match the Figma text styles (Display/XL 96, Display/L 64, Display/M 44, Heading/S 26, Quote 44, Body/L 20, Body/M 16, Body/S 14, Label/Eyebrow 12, Label/Nav 14, Mobile/Display L 44, Mobile/Display M 32, Mobile/Quote 26). Read weights, line heights and letter spacing from Figma.
- [x] **Headings trim.** Fraunces headings use leading trim "cap height to baseline" (CSS `text-box: trim-both cap alphabetic`, or the equivalent margin trim where unsupported).
- [x] **Fraunces WONK axis = 0.** Token `type/wonk` (`--type-wonk`). Apply `font-variation-settings: "WONK" 0` to all Fraunces text, so the leaning n/m/h forms are off. Load the variable Fraunces font so the axis works.
- [x] **Section spacing.** Top and bottom padding of every page section uses `section/desktop` and `section/mobile` (`--section-space-desktop`, `--section-space-mobile`). Read the current values from Figma. One variable controls site-wide vertical rhythm.
- [x] **Colour tokens.** `action/primary` (button fill, brand Yellow #F9E577), `action/on-primary` (button label, Dark Grey #394959), `action/primary-hover`, `surface/inverse` (Dark Grey), `text/inverse` (white), `surface/highlight` (eyebrows on dark sections), `border/inverse`.

## 2. Components

- [x] **Button** ([3:1967](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=3-1967)). All site buttons use the **Yellow** style: yellow fill, dark grey label. Match the **Hover** and **Focus** variants exactly as in Figma; the hover was reworked so the label stays legible. Hover transition 150ms ease-out. The Dark style still exists but isn't used. *Changed after review (19 November 2026): the Yellow hover is a dark grey fill with a 2px yellow border and yellow label, so `action/primary-hover` isn't used ([build plan](build-plan.md)).*
- [x] **Header / nav** ([7:22](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=7-22)). Nav labels use Label/Nav at 14px. The current page uses Label/Nav · Active (underlined). **Not final:** the client wants the nav bigger still, so expect another change.
- [x] **Footer** ([7:108](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=7-108)).
  - Desktop: link columns on top. The bottom row has the legal links and copyright on the left and both logos on the **bottom right**, bottom-aligned. The Grosvenor and Pimlico Road logos are the same width (240px).
  - Mobile: logos last, aligned right.
  - Column headings 20px, links 16px.
  - **Don't underline the current page's link in the footer.**
- [x] **Story card / tiles** ([3:1714](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=3-1714)). The link line under tiles ("Step inside", "Book a table" and so on) is **removed**. It's behind a `Show link` option, off by default. The whole card stays clickable.
- [x] **Field** ([13:99](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=13-99)). Applies to inputs, selects and textareas.
  - 16px horizontal and 12px vertical inner padding in every state.
  - **Focus/active state:** white background box. The bottom border stays (2px). Transition 200ms ease-out.
- [x] **Page title** ([7:32](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=7-32)). The title wraps within a 1040px max width on desktop, so it never overflows.
- [x] **Newsletter** ([7:58](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=7-58)). It appears above the footer on **every** page, desktop and mobile.
- [x] **Pagination** ([7:252](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=7-252)). Used on Brands, Dine, News and Search results.
- [ ] **Story row · Mobile** ([7:271](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=7-271)). The mobile list row used on search results. *On hold with search (19 Nov 2026).*

## 3. Pages

Each line links the desktop frame, then the mobile frame.

### Home ([desktop 9:425](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=9-425) · [mobile 9:1503](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=9-1503))
- [x] Hero heading bottom left, **"Pause film" control bottom right**, on both desktop and mobile.

### Newson's Yard ([desktop 23:735](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=23-735) · [mobile 21:507](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=21-507))
- [x] The hero follows the homepage layout: heading bottom left, pause bottom right.
- [x] **CMS:** the hero supports either a **video or an image**. Hide the pause control when it's an image.
- [x] Remove the "Hire the space" section.
- [x] Order: hero, resident brands, history/introduction, Visit banner, newsletter.
- [x] Copy note: the desktop hero heading still uses the homepage line. Use the Newson's Yard copy, as on mobile.

### Brands ([desktop 9:663](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=9-663) · [mobile 9:1685](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=9-1685))
- [x] The category filter takes **All plus about eight categories**. Desktop fits on one line; mobile is a horizontally scrolling row.
- [x] **CMS:** categories must be editable. The names in Figma are placeholders.

### Brand detail ([desktop 9:929](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=9-929) · [mobile 9:1827](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=9-1827))
- [x] **CMS:** build the page from optional, reorderable sections. The **Quote** section must be optional.
- [x] "Find the showroom": rows are Address, Opening hours, Trade and Contact (email). The accessibility row is removed. Buttons: "Get directions" and "Visit the website" (stacked full width on mobile).

### Dine ([desktop 85:5730](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=85-5730) · [mobile 86:2555](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=86-2555))
- [x] Categories: **All, Pubs and restaurants, Cafés**. No wine and bars.

### Events
- [x] **Remove** the event listing and event detail templates, and any "event", "gathering" or "upcoming events" links and cards elsewhere.

### Visit ([desktop 23:950](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=23-950) · [mobile 22:642](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=22-642))
- [x] Remove the "Useful information" and "Upcoming events" sections.
- [ ] "Find Pimlico Road" image is bigger (desktop 640 × 480, mobile 342 × 256), with a margin on the right so it isn't cropped at the edge. *Superseded: the section is removed on desktop in Figma, and the mobile page now follows the desktop order, so it's removed on both (decision 19 Nov 2026).*
- [x] "Getting here" is a **dark inverse section** on desktop (`surface/inverse`, white text, highlight eyebrow). On mobile the dark section is "Location & directions". *Dark on mobile too, as mobile now follows the desktop order.*

### Contact ([desktop 30:3805](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=30-3805) · [mobile 28:1031](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=28-1031))
- [x] "Enquiry routes" is a **dark inverse section**: white text, highlight eyebrow, inverse row dividers and light links. It applies in all form states: [error 37:4685](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=37-4685), [success 37:4779](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=37-4779).
- [x] Form fields use the new Field active state.

### About Pimlico Road ([desktop 27:1355](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=27-1355) · [mobile 27:3640](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=27-3640))
- [x] "Area story" is a **dark inverse section**.

### Press article ([template 27:1216](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=27-1216) · [FT example, desktop 160:4149](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=160-4149) · [mobile template 26:2098](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=26-2098) · [FT example, mobile 175:4743](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=175-4743))
- [x] **CMS:** optional **publication logo** field. It shows above the publication meta in the Publication section, and the layout works without it. Display it at 240 × 40 on desktop and 180 × 30 on mobile, scaled to fit.
- [x] Long headlines wrap within the 1040px title width.
- [x] The Source section image has an 80px right margin; it no longer runs to the edge.
- [x] Related content shows brands only, with no event card.

### Search ([desktop 174:2763](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=174-2763) · [mobile 175:4871](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=175-4871)) **New page**
- [ ] Title "Search", then the search field and Search button, then "Start exploring" with Brands, Dine and Newson's Yard. *On hold: search is under discussion (19 Nov 2026).*
- [ ] The search input has no visible label above it. Keep an accessible label (`aria-label` or a visually hidden `<label>`). *On hold: search is under discussion (19 Nov 2026).*
- [ ] Opens from the search icon in the header. *On hold: search is under discussion (19 Nov 2026).*

### Search results ([desktop 174:2916](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=174-2916) · [mobile 175:5029](https://www.figma.com/design/REw9zZENN4OEozQHrt17pD/Pimlico-Visuals---V01?node-id=175-5029)) **New page**
- [ ] Heading **"Search results"**, then the query line ("Results for 'antiques'"), the search field with the query filled in, and filter tabs with counts. *On hold: search is under discussion (19 Nov 2026).*
- [ ] **12 results per page** with pagination. Desktop is a grid of 3 × 4 cards; mobile is a list of Story rows. *On hold: search is under discussion (19 Nov 2026).*
- [ ] Results mix content types, each with its meta label: Brand, Dine, News, Press, Newson's Yard. *On hold: search is under discussion (19 Nov 2026).*
- [ ] Filter tabs: All, Brands, Dine, News, Press, Newson's Yard. **Only show a tab when it has at least one result** (never "Dine (0)"). *On hold: search is under discussion (19 Nov 2026).*

## 4. Search behaviour (client concern)

The client has had poor search on past sites. Agree the approach before building:

- [ ] Index brands, Dine venues, news, press and Newson's Yard pages. *On hold: search is under discussion (19 Nov 2026).*
- [ ] Match on names, categories, tags and body text as well as titles. Allow partial words and minor misspellings. *On hold: search is under discussion (19 Nov 2026).*
- [ ] Rank exact name matches first (for example, a brand name). *On hold: search is under discussion (19 Nov 2026).*
- [ ] Have a no-results state with suggestions (not yet designed; flag it to design). *On hold: search is under discussion (19 Nov 2026).*
- [ ] Before launch, test against a list of real queries from the client. *On hold: search is under discussion (19 Nov 2026).*

## 5. Final checks

- [ ] Every page at 1440 and 390 matches its Figma frame. *Checked by the build agents at 1440 and 390; needs a final pass by design.*
- [x] No text sizes outside the nine font-size tokens.
- [ ] Section padding uses the two section variables everywhere. *Partly: every band bound to the section variables in Figma uses them. A few bands (info band desktop, visit banner, hero split bottom, promo band) keep Figma's own unbound values.*
- [ ] Buttons, fields and links show the correct hover and focus states. *Built to Figma and checked in the compiled CSS; hover and keyboard focus still need checking by hand.*
- [x] Dark sections have readable text and visible dividers.
- [x] Footer: logos bottom right, no current-page underline.

## Not covered here

James has also edited Figma directly between rounds (for example, the Visit page restructure into "The street"). Before signing off, compare the current file in Figma's version history against the version the build started from, and add anything this list misses.
