# CLI-first Drupal workflows

Before writing custom code, use Drush generators and CLI tools to scaffold boilerplate and keep Drupal best practices in place.

## Content types and fields

- Use CLI commands to create content types and fields instead of manual configuration or PHP code.
- Prefer Drush field:create for field additions.

```bash
# Create a string field on an article content type
drush field:create node article \
  --field-name=field_subtitle \
  --field-label="Subtitle" \
  --field-type=string \
  --field-widget=string_textfield \
  --is-required=0 \
  --cardinality=1
```

```bash
# Create a reference field
drush field:create node article \
  --field-name=field_tags \
  --field-label="Tags" \
  --field-type=entity_reference \
  --field-widget=entity_reference_autocomplete \
  --cardinality=-1 \
  --target-type=taxonomy_term
```

## Generate module scaffolding

```bash
drush generate module
drush generate controller
drush generate form-simple
drush generate form-config
drush generate plugin:block
drush generate service
drush generate hook
drush generate event-subscriber
drush generate entity:content
drush generate entity:configuration
drush generate test:kernel
drush generate test:browser
```

## Create test content

```bash
drush devel-generate:content 50 --bundles=article,page --kill
drush devel-generate:terms 100 tags --kill
drush devel-generate:users 20
drush devel-generate:media 30 --bundles=image,document
```

## Workflow best practices

1. Start with generators before custom implementation.
2. Use field:create for all field additions.
3. Export configuration after CLI changes.
4. Document scaffolding commands in the project README.

```bash
drush config:export -y
```

## Avoid common mistakes

- Do not manually create content type config files or field config files when Drush can handle it.
- Use drush generate for scaffolding.
- Use drush php:eval only for automation or scripts, not as the default workflow.

## DDEV and Docker

```bash
# DDEV
ddev drush generate module
ddev drush field:create node article

# Docker Compose
docker compose exec php drush generate module
docker compose exec php drush field:create node article
```

## Non-interactive mode for automation and AI agents

Use --answers JSON or --answer flags to avoid interactive prompts.

```bash
drush generate module --answers='{
  "name": "My Custom Module",
  "machine_name": "my_custom_module",
  "description": "A custom module",
  "package": "Custom"
}'
```

```bash
drush generate controller --answers='{
  "module": "my_custom_module",
  "class": "MyController",
  "services": ["entity_type.manager", "current_user"]
}'
```

## Essential Drush commands

```bash
drush cr
drush cex -y
drush cim -y
drush updb -y
drush en module_name
drush pmu module_name
drush ws --severity=error
drush php:eval "code"
drush generate
drush gen module
drush field:create
drush entity:create
```
