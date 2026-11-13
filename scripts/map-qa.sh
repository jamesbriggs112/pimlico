#!/usr/bin/env bash
# Runs the map project's browser test suite (tests/qa.mjs, 96 checks) against
# this site's pages instead of the map project's demos.
#
#   scripts/map-qa.sh [base-url]        (default https://pimlico.test)
#
# Needs the map project checked out beside this one (or MAP_REPO set) with
# its npm dependencies installed, Chrome, and sample Brands with Units (Jamb
# must be on the map, and a page must carry the Street map paragraph at /visit).
set -euo pipefail

BASE="${1:-https://pimlico.test}"
MAP_REPO="${MAP_REPO:-$(cd "$(dirname "$0")/../.." && pwd)/pimlico-map}"
[ -f "$MAP_REPO/tests/qa.mjs" ] || { echo "Map project not found at $MAP_REPO (set MAP_REPO)." >&2; exit 2; }

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
mkdir -p "$WORK/tests"
for dir in node_modules output data; do ln -s "$MAP_REPO/$dir" "$WORK/$dir"; done

ASSETS=/modules/custom/pimlico_map/assets
# Point the suite at Drupal: asset and feed URLs, the Visit and Jamb pages,
# and the theme's filter bar (data-map-filter buttons with data-category).
# Drupal never renders a map for an unknown brand, so that check switches the
# Visit map to an unknown brand instead of loading a page.
sed \
  -e "s#BASE + '/map/pimlico-map.js'#BASE + '$ASSETS/pimlico-map.js'#" \
  -e "s#src=\"/map/pimlico-map.js\"#src=\"$ASSETS/pimlico-map.js\"#g" \
  -e "s#const SRC = '/output/map/pimlico-map.json', BRANDS = '/output/map/brands.json';#const SRC = '$ASSETS/pimlico-map.json', BRANDS = '/api/map/brands';#" \
  -e "s#'/demo/overview.html'#'/visit'#g" \
  -e "s#'/demo/brand.html?brand=jamb'#'/brands/jamb'#g" \
  -e "s#'\#map'#'pimlico-map'#g" \
  -e "s#document.getElementById('map')#document.querySelector('pimlico-map')#g" \
  -e "s/'#filters button'/'[data-map-filter] button'/g" \
  "$MAP_REPO/tests/qa.mjs" > "$WORK/tests/qa.mjs"
perl -pi -e 's/dataset\.cat\b/dataset.category/g' "$WORK/tests/qa.mjs"
perl -0pi -e "s#  const q = await page\('/demo/brand.html\?brand=does-not-exist'\);\n  await ready\(q, 'pimlico-map'\);#  const q = await page('/visit');\n  await ready(q, 'pimlico-map');\n  await q.evaluate(() => { const m = document.querySelector('pimlico-map'); m.setAttribute('mode', 'brand'); m.setAttribute('brand', 'does-not-exist'); });\n  await sleep(800);#" "$WORK/tests/qa.mjs"

cd "$WORK"
QA_BASE="$BASE" node tests/qa.mjs
