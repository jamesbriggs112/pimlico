# Pimlico migrate

Imports the 47 Brands and their Units from the map project's data.

1. Rebuild the source if the map project's data changed:
   `php scripts/build-source.php ../../../../../pimlico-map`
   (writes `data/brands.json`; check the diff).
2. `drush en pimlico_migrate`
3. `drush migrate:import --tag=pimlico`
4. Check the flagged placements (map guide, B7) with the Unit map picker,
   and fill in the six missing postcodes (`drush migrate:messages` lists
   nothing for these; search Content » Units for an empty postcode).
5. Uninstall the module after launch.

`drush migrate:rollback --tag=pimlico` removes everything it created.
