#!/usr/bin/env bash
cd /var/app/current && ./vendor/bin/drush updatedb --no-cache-clear && ./vendor/bin/drush cache:rebuild
