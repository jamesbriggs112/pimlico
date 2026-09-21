# Core Drupal principles

## 1. Follow Drupal coding standards

- Follow Drupal coding standards and PSR-4 autoloading.
- Keep classes in src/ and use proper docblocks.
- Use t() for user-facing strings with the correct placeholder style:
  - @variable for sanitized text
  - %variable for emphasized text
  - :variable for URLs

```php
// Good: user-facing string is translatable
$this->t('Submit form');
```

## 2. Use dependency injection

- Never use \Drupal::service() inside classes; inject services via the constructor.
- Define services in \*.services.yml.
- Use ContainerInjectionInterface for forms and controllers.
- Use ContainerFactoryPluginInterface for plugins.

```php
class MyController implements ContainerInjectionInterface {
  public function __construct(protected AccountProxyInterface $currentUser) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('current_user'));
  }
}
```

## 3. Hooks vs event subscribers

Both are valid in modern Drupal. Choose based on context:

- Use OOP hooks when altering core or contrib behavior and when hook order matters.
- Use event subscribers for third-party integrations and event-heavy workflows.

```php
#[Hook('form_alter')]
public function formAlter(&$form, FormStateInterface $form_state, $form_id): void {
  // ...
}

public static function getSubscribedEvents() {
  return [KernelEvents::REQUEST => ['onRequest', 100]];
}
```

## 4. Security first

- Never trust user input.
- Use parameterized queries and safe rendering methods.
- Check access permissions properly.
- Use #markup with Xss::filterAdmin() or #plain_text when needed.
- Review Drupal-specific OWASP risks before shipping custom code.

```php
$query = $this->database->select('node', 'n');
$query->fields('n', ['nid', 'title']);
$query->condition('n.type', $type);
$results = $query->execute();
```
