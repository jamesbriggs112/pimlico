<?php

declare(strict_types=1);

namespace Drupal\pimlico_map\Hook;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Brand and Unit addresses: street address and postcode only.
 *
 * Every Brand and Unit is on Pimlico Road, London.
 * Town (London) and country (United Kingdom) are filled in on save; county
 * and the third address line are hidden by the fields' overrides.
 */
final class AddressHooks {

  /**
   * Address fields that always hold a Pimlico Road address.
   */
  private const FIELDS = [
    'node' => 'field_address',
    'pimlico_unit' => 'address',
  ];

  /**
   * Implements hook_field_widget_single_element_address_default_form_alter().
   *
   * Hides the country select: United Kingdom is the only choice.
   */
  #[Hook('field_widget_single_element_address_default_form_alter')]
  public function hideCountry(array &$element, FormStateInterface $form_state, array $context): void {
    $definition = $context['items']->getFieldDefinition();
    if ((self::FIELDS[$definition->getTargetEntityTypeId()] ?? NULL) !== $definition->getName()) {
      return;
    }
    $element['address']['#after_build'][] = [self::class, 'hideCountryElement'];
  }

  /**
   * After-build callback: hides the country select.
   */
  public static function hideCountryElement(array $element): array {
    if (isset($element['country_code'])) {
      $element['country_code']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Implements hook_entity_presave().
   *
   * Fills in London and the United Kingdom.
   */
  #[Hook('entity_presave')]
  public function fillTown(mixed $entity): void {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $field = self::FIELDS[$entity->getEntityTypeId()] ?? NULL;
    if (!$field || !$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return;
    }
    $address = $entity->get($field)->first()->getValue();
    $address['country_code'] = 'GB';
    $address['locality'] = $address['locality'] ?: 'London';
    $address['administrative_area'] = '';
    $entity->get($field)->setValue([$address]);
  }

}
