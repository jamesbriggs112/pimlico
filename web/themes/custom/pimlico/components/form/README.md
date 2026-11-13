# Form

Wraps Drupal and Webform forms and styles their elements to the Figma "Field" and "Checkbox" components:

- Inputs, selects and textareas are underlined (1px rule; 2px on focus and on error).
- Labels use the eyebrow style.
- Errors show the alert icon and the message, from Drupal's `.form-item--error-message`.
- Checkboxes are 20px squares that fill yellow with a check when ticked.
- Focus uses the two-tone focus ring.

Form element markup comes from Drupal; this component only provides the wrapper and styles.
