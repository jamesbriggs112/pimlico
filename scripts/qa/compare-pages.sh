#!/usr/bin/env bash
#
# Compares two snapshot-pages.sh runs and lists the pages whose markup
# differs. Whitespace, Twig debug comments and the randomly picked story
# cards ("Similar brands", the search page's suggestions) are ignored.
#
#   scripts/qa/compare-pages.sh /tmp/pages-before /tmp/pages-after [-v]
#
# -v prints each page's diff, with whitespace kept, for reading.
set -uo pipefail

before=${1:?Usage: compare-pages.sh <before-dir> <after-dir> [-v]}
after=${2:?Usage: compare-pages.sh <before-dir> <after-dir> [-v]}
verbose=${3:-}

strip_debug() {
  grep -vE '▪️|✅|FILE NAME SUGGESTIONS|THEME HOOK|BEGIN OUTPUT|END OUTPUT|THEME DEBUG|og:updated_time' "$1"
}
normalise() {
  strip_debug "$1" | perl -0pe 's/<!--.*?-->//gs; s/<a  data-component-id="pimlico:story_card".*?<\/a>/CARD/gs; s/\s+//g'
}

diff -q "$before/_status.txt" "$after/_status.txt" >/dev/null || diff "$before/_status.txt" "$after/_status.txt"
changed=0
for file in "$before"/*.html; do
  name=$(basename "$file")
  [ -f "$after/$name" ] || { echo "missing: $name"; changed=1; continue; }
  if ! cmp -s <(normalise "$file") <(normalise "$after/$name"); then
    echo "differs: $name"
    changed=1
    [ "$verbose" = -v ] && diff <(strip_debug "$file") <(strip_debug "$after/$name") | head -40
  fi
done
[ "$changed" = 0 ] && echo "No markup differences."
exit "$changed"
