<?php

/**
 * @file
 * Post-update functions for Pimlico Map.
 */

declare(strict_types=1);

/**
 * Unit addresses hide county, town and the third line (set in code).
 */
function pimlico_map_post_update_unit_address_overrides(): string {
  $manager = \Drupal::entityDefinitionUpdateManager();
  $definition = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('pimlico_unit')['address'];
  $manager->updateFieldStorageDefinition($definition);
  return 'Unit address form simplified.';
}

/**
 * Unit addresses default to London, United Kingdom.
 */
function pimlico_map_post_update_unit_address_default(): string {
  $manager = \Drupal::entityDefinitionUpdateManager();
  $definition = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('pimlico_unit')['address'];
  $manager->updateFieldStorageDefinition($definition);
  return 'Unit addresses default to London.';
}
