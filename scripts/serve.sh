#!/usr/bin/env bash
# Serves the site locally with PHP's built-in web server, for machines
# without Laravel Valet. See docs/local-development.md.
#
# Usage: scripts/serve.sh [port]   (default port 8888)

set -euo pipefail

PORT="${1:-8888}"
PHP_BIN="${PHP_BIN:-/opt/homebrew/opt/php@8.4/bin/php}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

if [[ ! -x "$PHP_BIN" ]]; then
  echo "PHP 8.4 not found at $PHP_BIN. Install it with: brew install php@8.4" >&2
  exit 1
fi

cd "$ROOT/web"
echo "Pimlico Road on http://localhost:$PORT (Ctrl+C to stop)"
exec "$PHP_BIN" -d memory_limit=512M -S "127.0.0.1:$PORT" .ht.router.php
