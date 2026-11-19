# Story card

Figma "A / Story card". The whole card is one link: a 4:5 image, the meta line (category and date) as a dark eyebrow, a Fraunces title and, only when `show_link` is true, a caps label ("Step inside", "Explore", "Read"). `show_link` is off by default, as in Figma, so callers' `link_text` is ignored unless they turn it on. Hovering zooms the image 5% and underlines the label; keyboard focus shows the focus ring around the card.

Used for news stories, brands ("Similar brands", the brands listing) and the Cards section. The label is `aria-hidden` because the card's accessible name is its text.
