#!/usr/bin/env bash
# drush deploy = updatedb, config:import, cache:rebuild, then deploy hooks.
# The skeleton ran only updatedb and cache:rebuild, so new content types,
# fields and settings never reached the server. Config Ignore keeps
# editor-managed config (webforms, mega menu panels) as edited on the server.
set -e
cd /var/app/current && ./vendor/bin/drush deploy --yes
