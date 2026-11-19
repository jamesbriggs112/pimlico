# Editor guide

How to look after the Pimlico Road website. Log in at `/user/login`.

## Brands

**Content » Add content » Brand.** A Brand is a business with its own page.

- **Title, Summary and Hero image** appear on the Brand page and on cards across the site.
- **Brand category**: the list matches the site. Under **Brands**, a category such as Interiors or Antiques & art puts it on the Brands page; under **Dine**, Pubs and restaurants or Cafés put it on Dine.
- **Brand card label**: a Brand's card on Dine, Brands and elsewhere shows its own category as a tag, such as "Cafés" or "Pubs and restaurants" on Dine and "Interiors" on Brands.
- **Categories and tags** (Structure » Taxonomy » Brand category): every top-level category except Food & Drink is a filter on the Brands page, in the order of the list, and every category under Food & Drink is a filter on Dine. To add a tag, choose **Add term**, enter its name and, under **Relations**, pick its **Parent term**:
  - a new Dine tag (say "Bakeries"): parent **Food & Drink**. It appears as a Dine filter and under **Dine** in the Brand's category list.
  - a new Brands category (say "Lighting"): parent **&lt;root&gt;**. It appears as a Brands filter and under **Brands** in the Brand's category list.

  The street map on Visit has the same filters: the Brands ones, then the Dine ones (a Brand under Lamps, below Lighting, shows under Lighting; one tagged Cafés under Cafés). A new tag appears there too, as soon as it is saved.

  Filters appear straight away, in the order of the list: drag terms to reorder them, and rename freely. Give a Brand the new tag to show it on its card. Don't delete and re-create Food & Drink: Dine would stop working. Deleting a category with Brands in it leaves those Brands with no category, so move them first.
- **Sections** build the page between the hero and "Find the showroom" (see [Adding sections](#adding-sections)). Each is optional, and you can drag them into any order. The usual set follows the design: **Introduction** ("About the house": eyebrow, heading, text), **Quote**, **Image gallery** ("Inside the showroom", with the photography credit as its caption), **Info rows** ("Specialisms" eyebrow, "What you'll find" heading, linen background, one row per specialism) and **Similar brands**. Add **Text** (a heading, paragraphs or both), **Image** and **Video** sections anywhere.
- **Address** (street and postcode: London and the United Kingdom are added automatically), **Opening hours** (one line per row, e.g. "Monday to Friday: 10am to 6pm"), **Trade, Email and Website** fill the fixed "Find the showroom" section (rows Address, Opening hours, Trade and Contact, and the "Get directions" and "Visit the website" buttons). **Phone** isn't shown on the page; search engines read it from the page's structured data.
- **Similar brands**: choose up to three, or leave empty to show three from the same category. They appear in the page's **Similar brands** section (see [Sections that fill themselves](#sections-that-fill-themselves)).

### Putting a Brand on the map

A Brand appears on the street map when it is **published**, **Tenancy status** is **Active**, **Display on map** is ticked, and it has a **Unit** that is placed on the map.

- **Units** are the shopfronts Brands trade from (Content » Units). Choose the Brand's Unit in the Brand's **Units** field.
- **Map label**: a short name (up to 24 characters) for the small label on the map, e.g. "Sibyl Colefax". The title is used when it's empty.
- When a Brand **closes**, set Tenancy status to Closed. Its building turns white; the Unit stays for the next tenant.
- When a Brand **moves**, change its Unit.

### Units and the map picker

**Content » Units » Add unit.** Give the shopfront a name ("95–97 Pimlico Road"), its address and Area, then click the shopfront on the map: its ID fills the **Map building** box. Hover over a building to see its address before clicking. Use **Add another item** if a Unit covers two buildings, and **Remove** to clear one.

If a building isn't on the map, choose the nearest one and ask the developers to update the base map.

## Stories (News)

**Content » Add content » Article or Press article.**

- **News category, Summary and Image** are shared. The News category shows on the article page and picks related stories; story cards (on `/news`, the home page and related stories) show the type and date instead, such as "News · 4 Nov" or "Press · 12 Nov". **Featured** puts the story at the top of `/news` (only one at a time is shown, the newest featured one).
- **Sections** are the body: add **Text** (headings and paragraphs), **Image** (one photo with its caption), **Image gallery**, **Video** and **Quote** sections in any order (see [Adding sections](#adding-sections)). Every Story ends with a **Related** section.
- **Related stories** (Articles): choose the stories the Related section shows ("Also on this street"), or leave empty to show stories in the same category.
- **Related brands** (Press articles): choose up to three Brands for the Related section ("Discover the places in the story"), or leave empty to show three Brands from the street, picked at random.
- **Press articles**: add the publication, its logo, the date, the journalist and the link to the original coverage. The link is kept for reference only; it isn't shown on the page.

## Adding sections

Brands, Stories and Pages are built from **Sections**. Under **Sections** on the edit form:

1. Choose **Add section** at the end, or the **+** between two sections to add one there.
2. Pick a section in the dialog. Each has a picture of its layout and a line saying what it's for; only the sections that suit the page you're editing are offered.
3. Fill it in, then drag the handle on the left to move it. **Remove** is in the section's menu.

The everyday sections, on Brands, Stories and Pages alike:

| Section | Use it for |
|---|---|
| Text | Headings and paragraphs. For a heading on its own, fill in only **Heading**. Inside the text, use **Heading 2** for a subheading and **Heading 3** below that. |
| Image | One photograph with an optional caption. On Pages and Brands, **Layout: Full width** shows it the width of the page. |
| Image gallery | Several photographs in rows (a swipe gallery on phones), with an optional heading and caption |
| Video | A YouTube or Vimeo link, or an uploaded film (see [Images and film](#images-and-film)) |
| Quote | A pull quote with the speaker's name and role |

### Sections that fill themselves

Two sections have nothing to fill in: they fill themselves from the page they're on. They're under **Automatic** in the dialog.

- **Similar brands** (Brands): the Brands chosen under **Similar brands** on the Brand, or three others in its category.
- **Related** (Articles and Press articles): the **Related stories** or **Related brands** chosen on the Story, or else stories in the same category (Articles) or three Brands from the street (Press articles).

Every new Brand and Story gets one, at the end of its sections, when it's first saved. As the last section it appears where the design puts it: below "Find the showroom" on a Brand and below the credits on a Story. Drag it further up to show it earlier, give it a **Heading** to replace the usual one ("Similar brands", "Also on this street" or "Discover the places in the story"), or remove it to show no cards.

## Pages

The sections made for Pages:

| Section | Use it for |
|---|---|
| Hero film / Split hero | The first section of a page only. A Hero film needs an **Image**; its **Film** is optional. With a film, the film plays over the image with a "Pause film" control; without one, the hero is the image alone (Newson's Yard can use either). |
| Introduction | A heading, text, a link and one or two photos |
| Cards | Three cards: pick Stories, Brands or pages, or leave empty for the latest stories. Background: white, linen or dark. **Show Brand intros** adds each Brand's intro under its name (Newson's Yard "Resident brands"); keep intros to one line for cards. |
| Feature banner / Visit banner | Full-width photo bands with a heading and button |
| Info rows | Labelled rows ("Getting here", "How can we help?"). Background: white, linen or dark. |
| Info band | A dark band with text and a photo |
| Split text | A heading on the left, longer text on the right ("A street with a story"). Background: white, linen or dark. |
| Street map | The interactive map with the category filter (Visit) |
| Policy text | Policies: Heading 2s become the "On this page" list automatically |
| Next step | Heading, text and up to three buttons. With only a heading and buttons it becomes the compact "Related information" bar. |
| Contact form | A form (Webform) with a heading |

**Dark background.** Cards, Info rows and Split text can be **Dark**: a dark grey section with white text, yellow eyebrows, light dividers and white links. The design uses it for Visit "Getting here", Contact "Enquiry routes" and About "Area story". Use it for one section at a time, not two dark sections in a row, and not next to an Info band or Visit banner, which are dark already.

## Images and film

- Upload photos at least 1600 px wide; they're cropped to fit each section.
- Always write alt text that says what's in the photo, unless it's purely decorative.
- **Focal point:** click the part of the photo that must stay in view. Cards, heroes and banners crop around it at every screen size.
- **YouTube videos**: in a Video section, choose **Add media » Remote video** and paste the YouTube link (a start time such as `?t=90` is kept). The video's thumbnail shows with a play button; nothing loads from YouTube until someone presses play. Vimeo links work too, in the standard player.
- **Hero films**: a 10–20 second silent loop, under 5 MB. Larger films use a lot of mobile data. The hero's image is required: it shows before the film plays and instead of it for people who prefer reduced motion (the film's first frame works well). Leave the film empty for an image-only hero.

## Publishing

Content goes through **Draft → Published**. Save as Draft to preview, then publish. Changes to Brands and Units reach the map as soon as you publish.
