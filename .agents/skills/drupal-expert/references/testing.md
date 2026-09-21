# Testing guidance

Tests are not optional for production code.

## Choose the right test type

- Unit tests for isolated logic and pure PHP transformations.
- Kernel tests for services, entities, hooks, and database interactions.
- Functional tests for forms, controllers, access control, and user flows.
- FunctionalJS tests for AJAX and JavaScript-driven behavior.

## Test placement

```text
my_module/
└── tests/
    └── src/
        ├── Unit/
        ├── Kernel/
        └── Functional/
```

## Example kernel test

```php
final class MyServiceTest extends KernelTestBase {
  protected static $modules = ['system'];

  public function testSomething(): void {
    $this->assertTrue(TRUE);
  }
}
```

## Running tests

```bash
./vendor/bin/phpunit modules/custom/my_module/tests/src/Unit/MyTest.php
./vendor/bin/phpunit modules/custom/my_module
./vendor/bin/phpunit --coverage-html coverage modules/custom/my_module
```

## When to write each type

- Unit tests: pure PHP logic and data transformations
- Kernel tests: services, queries, entities, and hooks
- Functional tests: access control, forms, controllers, and workflows
- FunctionalJS tests: AJAX, dynamic forms, and interactive behavior
