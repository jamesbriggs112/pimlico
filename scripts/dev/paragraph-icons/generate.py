#!/usr/bin/env python3
"""Draw the Paragraph type icons shown in the "Add section" dialog.

Each icon is a wireframe of the section's layout in the Pimlico Road palette:
linen ground, slate text and panels, grey photographs, yellow buttons and
pumpkin eyebrows. Run it, then attach the icons with set-icons.php:

  python3 scripts/dev/paragraph-icons/generate.py
  vendor/bin/drush php:script scripts/dev/paragraph-icons/set-icons.php
  vendor/bin/drush cex
"""

from pathlib import Path

W, H = 240, 144
SLATE, GREY, YELLOW, PUMPKIN, WHITE, LINEN = (
    '#394959', '#c5d3d2', '#f9e577', '#e87400', '#ffffff', '#f4f2eb')
OUT = Path(__file__).parent / 'icons'


def rect(x, y, w, h, fill, rx=0, extra=''):
    return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}" fill="{fill}"{extra}/>'


def ground(fill=LINEN):
    return rect(0, 0, W, H, fill)


def heading(x, y, w, fill=SLATE, h=8):
    return rect(x, y, w, h, fill, 1)


def text(x, y, widths, fill=SLATE, gap=8, opacity=0.35, h=4):
    return ''.join(rect(x, y + i * gap, w, h, fill, 2, f' opacity="{opacity}"')
                   for i, w in enumerate(widths))


def centred_text(y, widths, fill=SLATE, gap=8, opacity=0.35, h=4):
    return ''.join(rect((W - w) / 2, y + i * gap, w, h, fill, 2, f' opacity="{opacity}"')
                   for i, w in enumerate(widths))


def eyebrow(x, y, fill=PUMPKIN, w=18):
    return rect(x, y, w, 3, fill, 1.5)


def button(x, y, w=34, fill=YELLOW, h=10):
    return rect(x, y, w, h, fill, 1)


def outline_button(x, y, w=34, stroke=SLATE, h=10, fill=LINEN):
    return (f'<rect x="{x + 0.75}" y="{y + 0.75}" width="{w - 1.5}" height="{h - 1.5}" rx="1" '
            f'fill="{fill}" stroke="{stroke}" stroke-width="1.5"/>')


def italic_line(x, y, w, h=7, slant=2, fill=SLATE):
    """A line of italic text: a parallelogram leaning right."""
    return (f'<polygon points="{x + slant},{y} {x + w + slant},{y} {x + w - slant},{y + h} '
            f'{x - slant},{y + h}" fill="{fill}"/>')


def rule(x, y, w, fill=SLATE, opacity=0.3):
    return rect(x, y, w, 1, fill, 0, f' opacity="{opacity}"')


def photo(x, y, w, h, fill=GREY, glyph=WHITE):
    """A photograph: a grey frame with a sun and hills, scaled to fit."""
    s = min(w, h) / 60
    cx, cy = x + w / 2, y + h / 2
    hills = (f'{cx - 18 * s},{cy + 12 * s} {cx - 6 * s},{cy - 2 * s} {cx + 2 * s},{cy + 6 * s} '
             f'{cx + 8 * s},{cy} {cx + 18 * s},{cy + 12 * s}')
    return (rect(x, y, w, h, fill)
            + f'<circle cx="{cx + 8 * s}" cy="{cy - 10 * s}" r="{4 * s}" fill="{glyph}"/>'
            + f'<polygon points="{hills}" fill="{glyph}"/>')


def play(cx, cy, r=14, colour=WHITE):
    return (f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{SLATE}" fill-opacity="0.6" stroke="{colour}" stroke-width="2"/>'
            f'<polygon points="{cx - r * 0.3},{cy - r * 0.42} {cx + r * 0.45},{cy} '
            f'{cx - r * 0.3},{cy + r * 0.42}" fill="{colour}"/>')


ICONS = {
    # Section heading over three story cards.
    'content_grid': ground() + heading(16, 16, 72) + ''.join(
        photo(16 + i * 72, 34, 64, 62) + heading(16 + i * 72, 102, 48, h=5)
        + text(16 + i * 72, 112, [60, 40]) for i in range(3)),

    # Centred eyebrow, title, text and Yellow buttons.
    'cta': ground() + eyebrow(111, 22) + heading(60, 34, 120, h=10) + heading(80, 49, 80, h=10)
    + centred_text(70, [150, 120]) + button(76, 98, 40) + button(124, 98, 40),

    # The wide (2:1) image with a caption.
    'image': ground() + photo(16, 14, 208, 100) + text(16, 122, [96], opacity=0.5),

    # A 3:2 photograph with its caption, as in the gallery.
    'image_with_caption': ground() + photo(66, 14, 108, 72) + text(66, 96, [100, 70]),

    # Pumpkin rule, an italic pull quote and the attribution.
    'quote': ground() + rect(105, 24, 30, 3, PUMPKIN, 1.5)
    + ''.join(italic_line((W - w) / 2, 40 + i * 15, w) for i, w in enumerate([170, 180, 120]))
    + centred_text(98, [60], opacity=0.5),

    # Long-form text at the reading measure.
    'text_wysiwyg_': ground() + heading(50, 18, 90) + text(50, 34, [140, 136, 140, 96])
    + heading(50, 72, 60, h=6) + text(50, 84, [140, 130])
    + ''.join(f'<circle cx="54" cy="{108 + i * 9}" r="2" fill="{SLATE}"/>'
              + rect(62, 106 + i * 9, [110, 90, 100][i], 4, SLATE, 2, ' opacity="0.35"') for i in range(3)),

    # A 16:9 film with the play control.
    'video': ground() + photo(32, 22, 176, 99, glyph='#dbe3e2')
    + rect(32, 22, 176, 99, SLATE, 0, ' opacity="0.25"') + play(120, 71.5, 16),

    # The Brand Category filter bar over the map, with brand pins.
    'street_map': ground() + rect(16, 14, 30, 10, SLATE, 5)
    + ''.join(f'<rect x="{52 + i * 40 + 0.75}" y="14.75" width="34.5" height="8.5" rx="4.25" '
              f'fill="{LINEN}" stroke="{SLATE}" stroke-opacity="0.5" stroke-width="1.5"/>' for i in range(4))
    + rect(16, 32, 208, 98, '#e3e9e7')
    + ''.join(rect(x, y, w, h, GREY) for x, y, w, h in [
        (24, 40, 40, 28), (70, 40, 54, 22), (130, 40, 30, 34), (166, 40, 50, 26),
        (24, 96, 60, 26), (92, 100, 44, 22), (150, 92, 66, 30)])
    + rect(16, 78, 208, 10, WHITE)
    + ''.join(f'<path d="M{x} {y} a6 6 0 1 1 12 0 c0 5 -6 10 -6 10 s-6 -5 -6 -10z" fill="{PUMPKIN}"/>'
              f'<circle cx="{x + 6}" cy="{y}" r="2.2" fill="{WHITE}"/>'
              for x, y in [(52, 52), (114, 58), (184, 50), (124, 104)]),

    # Dark band: white heading, ruled rows and buttons beside a photo.
    'info_band': ground(SLATE) + heading(16, 20, 92, WHITE) + heading(16, 32, 64, WHITE)
    + ''.join(rule(16, 52 + i * 16, 104, WHITE, 0.4) + text(16, 57 + i * 16, [36], WHITE, opacity=0.9)
              + text(60, 57 + i * 16, [56], WHITE, opacity=0.5) for i in range(3))
    + button(16, 110, 40) + photo(136, 16, 88, 112),

    # Breadcrumbs, title, intro and buttons beside a tall photograph.
    'hero_split': ground() + text(16, 18, [44], opacity=0.5, h=3) + heading(16, 32, 96, h=11)
    + heading(16, 48, 72, h=11) + text(16, 70, [100, 90, 60]) + button(16, 102, 38)
    + outline_button(60, 102, 38) + photo(132, 0, 108, 144),

    # Full-bleed film under a scrim, the heading and a pause control.
    'hero_film': photo(0, 0, W, H, glyph='#dbe3e2')
    + ''.join(rect(0, 40 + i * 6, W, H - 40 - i * 6, SLATE, 0, ' opacity="0.075"') for i in range(17)) + heading(16, 92, 120, WHITE, 11) + heading(16, 108, 84, WHITE, 11)
    + f'<circle cx="214" cy="118" r="10" fill="{SLATE}" stroke="{WHITE}" stroke-width="1.5"/>'
    + rect(210, 113, 3, 10, WHITE) + rect(215, 113, 3, 10, WHITE),

    # Full-bleed photograph under a dark scrim, centred text and a button.
    'feature_banner': photo(0, 0, W, H, glyph='#dbe3e2') + rect(0, 0, W, H, SLATE, 0, ' opacity="0.72"')
    + rect(110, 22, 20, 3, WHITE, 1.5, ' opacity="0.8"')
    + heading(65, 44, 110, WHITE, 10) + centred_text(64, [140, 110], WHITE, opacity=0.6)
    + button(100, 92, 40),

    # Text and a link beside a two-photo collage.
    'introduction': ground() + eyebrow(16, 22) + heading(16, 32, 88) + heading(16, 44, 70)
    + text(16, 62, [96, 100, 80]) + text(16, 96, [44], opacity=1, h=3) + rule(16, 102, 44, SLATE, 1)
    + photo(126, 14, 72, 92) + rect(166, 60, 62, 74, LINEN) + photo(170, 64, 54, 66),

    # A short intro on the left, the content column on the right.
    'split_text': ground() + eyebrow(16, 20) + heading(16, 30, 64) + heading(16, 42, 50)
    + text(16, 60, [70, 60]) + text(104, 20, [120, 116, 120, 90]) + text(104, 62, [120, 112, 120, 104, 70]),

    # Photograph beside a dark panel: eyebrow, title, rows and a button.
    'visit_banner': ground() + photo(0, 0, 112, H) + rect(112, 0, 128, H, SLATE) + eyebrow(128, 20, YELLOW)
    + heading(128, 30, 88, WHITE) + heading(128, 42, 64, WHITE) + text(128, 60, [96, 80], WHITE, opacity=0.5)
    + rule(128, 84, 96, WHITE, 0.4) + text(128, 90, [70], WHITE, opacity=0.8)
    + rule(128, 100, 96, WHITE, 0.4) + button(128, 112, 40),

    # Landscape and portrait photographs in rows.
    'image_gallery': ground() + photo(16, 16, 80, 54) + photo(102, 16, 44, 54) + photo(152, 16, 72, 54)
    + photo(16, 76, 44, 54) + photo(66, 76, 80, 54) + photo(152, 76, 72, 54),

    # The "On this page" list beside the policy text.
    'policy_content': ground() + text(16, 18, [48], opacity=1, h=4)
    + ''.join(rule(16, 32 + i * 18, 64) + rect(16, 38 + i * 18, 6, 6, PUMPKIN, 1)
              + text(28, 39 + i * 18, [44, 0][:1]) for i in range(5))
    + text(100, 18, [50], opacity=0.5, h=3) + text(100, 30, [124, 110], opacity=0.7, gap=10, h=5)
    + heading(100, 58, 70, h=6) + text(100, 72, [124, 118, 124, 96, 120, 80]),

    # A heading on the left, ruled rows of title and text on the right.
    'info_rows': ground() + heading(16, 20, 72) + heading(16, 32, 50)
    + ''.join(rule(104, 20 + i * 38, 120) + heading(104, 30 + i * 38, 36, h=5)
              + text(150, 30 + i * 38, [74, 60]) for i in range(3)) + rule(104, 128, 120),

    # One row: a title beside its text, between rules.
    'info_row': ground() + rule(24, 36, 192, SLATE, 0.5) + heading(24, 54, 56, h=8)
    + text(100, 54, [116, 108, 116, 70], gap=10, h=5) + rule(24, 108, 192, SLATE, 0.5),

    # Eyebrow and title on the left, the form on the right.
    'webform': ground() + eyebrow(16, 22) + heading(16, 32, 72) + heading(16, 44, 54)
    + text(16, 62, [76, 60])
    + ''.join(text(104, 16 + i * 26, [30], opacity=0.6, h=3)
              + rect(104.75, 22.75 + i * 26, 118.5, 12.5, WHITE, 1, f' stroke="{SLATE}" stroke-opacity="0.4" stroke-width="1.5"')
              for i in range(3))
    + button(104, 102, 44),
}


def main():
    OUT.mkdir(exist_ok=True)
    for name, body in ICONS.items():
        svg = (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" '
               f'width="{W}" height="{H}">{body}</svg>\n')
        (OUT / f'{name}.svg').write_text(svg)
    print(f'Wrote {len(ICONS)} icons to {OUT}')


if __name__ == '__main__':
    main()
