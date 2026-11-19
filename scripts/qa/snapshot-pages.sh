#!/usr/bin/env bash
#
# Saves the HTML of every node page and the main listings, so a refactor can
# be checked page by page with compare-pages.sh.
#
#   scripts/qa/snapshot-pages.sh /tmp/pages-before [http://pimlico.test]
#
# Run from the project root. Uses http: Valet serves https only once the
# site is secured. Values that change on every request (form tokens, Sentry
# trace IDs, random gallery IDs) are masked so two runs of an unchanged site
# match.
set -euo pipefail

out=${1:?Usage: snapshot-pages.sh <out-dir> [base-url]}
base=${2:-http://pimlico.test}
mkdir -p "$out"

{
  vendor/bin/drush sql:query "SELECT alias FROM path_alias WHERE path LIKE '/node/%' ORDER BY id"
  printf '%s\n' / /brands '/brands?category=interiors' '/brands?category=antiques-and-art' \
    /dine /news /search '/search?keys=house' /form/contact /llms.txt /api/map/brands /user/login
} > "$out/_urls.txt"

: > "$out/_status.txt"
while read -r url; do
  [ -z "$url" ] && continue
  name=$(printf '%s' "$url" | sed 's|^/||; s|[/?=&]|_|g')
  name=${name:-front}
  code=$(curl -s -o "$out/$name.raw" -w '%{http_code}' "$base$url")
  sed -E \
    -e 's/form-[A-Za-z0-9_-]{20,}/FORM_ID/g' \
    -e 's/(value|content)="[A-Za-z0-9_-]{40,}"/\1="TOKEN"/g' \
    -e 's/name="captcha_sid" value="[0-9]+"/captcha_sid/g' \
    -e 's/<meta name="(baggage|sentry-trace)"[^>]*>/SENTRY/g' \
    -e 's/gallery-(track-)?[0-9]+/gallery-N/g' \
    -e 's/\?[a-z0-9]{6}"/?CACHEBUST"/g' \
    "$out/$name.raw" > "$out/$name.html"
  rm "$out/$name.raw"
  echo "$code $url" >> "$out/_status.txt"
done < "$out/_urls.txt"

echo "$(grep -c . "$out/_status.txt") pages saved in $out; not 200:"
grep -v '^200 ' "$out/_status.txt" || echo "  none"
