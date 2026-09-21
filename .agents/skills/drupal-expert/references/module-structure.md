# Module structure and implementation patterns

## Recommended module layout

```text
my_module/
├── my_module.info.yml
├── my_module.module           # Hooks only; keep thin
├── my_module.services.yml     # Service definitions
├── my_module.routing.yml      # Routes
├── my_module.permissions.yml  # Permissions
├── my_module.libraries.yml    # CSS/JS libraries
├── config/
│   ├── install/
│   ├── optional/
│   └── schema/                # Required for custom config
├── src/
│   ├── Controller/
│   ├── Form/
│   ├── Plugin/
│   │   ├── Block/
│   │   └── Field/
│   ├── Service/
│   ├── EventSubscriber/
│   └── Hook/
├── templates/                 # Twig templates
└── tests/
    └── src/
        ├── Unit/
        ├── Kernel/
        └── Functional/
```

## Common implementation patterns

- Keep hooks thin and place logic in services or classes.
- Use service definitions for reusable behavior.
- Add config schema for custom configuration objects.
- Use the database abstraction layer rather than raw SQL.
- Add cache metadata to render arrays for correctness and performance.

## Service example

```yaml
services:
  my_module.my_service:
    class: Drupal\my_module\Service\MyService
    arguments: ['@entity_type.manager', '@current_user', '@logger.factory']
```

## Route example

```yaml
my_module.page:
  path: '/my-page'
  defaults:
    _controller: '\Drupal\my_module\Controller\MyController::content'
    _title: 'My Page'
  requirements:
    _permission: 'access content'
```

## Plugin example

```php
#[Block(
  id: 'my_block',
  admin_label: new TranslatableMarkup('My Block'),
)]
class MyBlock extends BlockBase implements ContainerFactoryPluginInterface {}
```

## Config schema example

```yaml
# config/schema/my_module.schema.yml
my_module.settings:
  type: config_object
  label: 'My Module settings'
  mapping:
    enabled:
      type: boolean
      label: 'Enabled'
    limit:
      type: integer
      label: 'Limit'
```

## Database queries

Always use the database abstraction layer:

```php
$query = $this->database->select('node', 'n');
$query->fields('n', ['nid', 'title']);
$query->condition('n.type', $type);
$query->range(0, 10);
$results = $query->execute();
```

## Cache metadata

Always add cache metadata to render arrays:

```php
$build['content'] = [
  '#markup' => $content,
  '#cache' => [
    'tags' => ['node_list', 'user:' . $uid],
    'contexts' => ['user.permissions', 'url.query_args'],
    'max-age' => 3600,
  ],
];
```

### Cache tag conventions

- node:123 for a specific node
- node_list for any node list
- user:456 for a specific user
- config:my_module.settings for configuration
