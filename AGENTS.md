# Agent Instructions for Four Drupal Skeleton

Always use Context7 MCP when I need library/API documentation, code generation, setup or configuration steps without me having to explicitly ask.

---

## Single Directory Components (SDC)

This project uses **Drupal's Single Directory Component (SDC)** system for all UI components. Components are self-contained units with their own templates, styles, scripts, and schemas.

### Quick Start

**Generate a new component:**
```bash
drush generate four-sdc
```

**Component location:**
```
web/themes/custom/four_starterkit/components/{component_name}/
```

**Generated files:**
- `{name}.component.yml` - Schema definition (props, slots, libraries)
- `{name}.twig` - Template
- `{name}.scss` - Styles (optional)
- `{name}.js` - JavaScript (optional)
- `{name}.stories.twig` - Storybook stories (optional)
- `README.md` - Documentation
- `thumbnail.png` - Visual preview

---

## Naming Conventions

- **Directory names:** `snake_case` (e.g., `accordion_item`, `button_icon`)
- **CSS classes:** `kebab-case` (e.g., `.accordion-item`, `.button-icon`)
- **Machine names:** `snake_case` in component.yml
- **Component reference:** `four_starterkit:component_name`

---

## Component Schema (component.yml)

### Standard Structure

```yaml
'$schema': 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json'
name: Component Display Name
status: stable
description: Brief description of the component's purpose
libraryOverrides:
  css:
    component:
      component_name.scss: {}
  js:
    component_name.js: {}
  dependencies:
    - core/once
    - core/drupal
props:
  type: object
  required: []  # Optional array of required prop names
  properties:
    prop_name:
      type: string  # string, number, boolean, integer, array, object
      title: Prop Display Name
      description: Description for documentation
      examples:
        - example_value
slots:
  slot_name:
    title: Slot Display Name
    description: Description of slot content
```

### Common Prop Types

```yaml
# String prop
text:
  type: string
  title: Text Content

# Boolean prop
is_active:
  type: boolean
  title: Active State
  default: false

# Enum (constrained values)
color:
  type: string
  title: Color Variant
  enum:
    - primary
    - secondary
    - tertiary
  default: primary

# Drupal Attribute object
attributes:
  type: Drupal\Core\Template\Attribute
  title: HTML Attributes

# Additional CSS classes
class:
  type: string
  title: Additional Classes

# Array of objects
items:
  type: array
  title: Menu Items
  items:
    type: object
    properties:
      title:
        type: string
      url:
        type: string
```

### Library Dependencies

Common libraries to include:
- `core/once` - Single initialization helper
- `core/drupal` - Drupal JavaScript API
- `core/drupal.ajax` - AJAX functionality
- `{theme}/base` - Theme base styles

---

## Twig Template Patterns

### Standard Template Boilerplate

```twig
{#
/**
 * @file
 * Component template for {Component Name}.
 */
#}
{% set attributes = attributes ?: create_attribute() %}
{% set attributes = attributes|without('class') %}
{% set classes = [
  'component-name',
  modifier ? 'component-name--' ~ modifier,
  is_active ? 'is-active',
  class
] %}

<element {{ attributes.addClass(classes) }}>
  {% block slot_name %}
    {{ content }}
  {% endblock %}
</element>
```

### Key Patterns

**Always handle attributes safely:**
```twig
{% set attributes = attributes ?: create_attribute() %}
{% set attributes = attributes|without('class') %}
```

**Build BEM classes with modifiers:**
```twig
{% set classes = [
  'button',
  color ? 'button--' ~ color,
  size ? 'button--' ~ size,
  is_disabled ? 'is-disabled',
  class
] %}
```

**Define slots with blocks:**
```twig
{% block content %}
  {{ content|default('Default content') }}
{% endblock %}
```

### Component Inclusion

**Include (simple composition):**
```twig
{% include "four_starterkit:button" with {
  text: 'Click me',
  url: '/path',
  color: 'primary'
} %}
```

**Embed (with slot overrides):**
```twig
{% embed "four_starterkit:card" with {
  class: 'custom-class'
} %}
  {% block image %}
    {{ drupal_field('field_image', 'node') }}
  {% endblock %}
  
  {% block content %}
    <h2>{{ title }}</h2>
    {{ body }}
  {% endblock %}
{% endembed %}
```

**Pass all props:**
```twig
{% include "four_starterkit:heading" with {
  tag: heading_tag,
  class: heading_class,
  attributes: title_attributes
} %}
```

---

## CSS/SCSS Conventions

### BEM Naming (Block Element Modifier)

**Always use BEM naming:**
```scss
.component-name {              // Block
  &__element {                 // Element
    &-subelement { }           // Sub-element (avoid deep nesting)
  }
  
  &--modifier {                // Modifier
    // Variant styles
  }
  
  &.is-active {                // State class
    // State styles
  }
}
```

**Examples:**
- `.button` - Block
- `.button__icon` - Element
- `.button--primary` - Modifier
- `.button.is-disabled` - State

### CSS Variables

**Use CSS custom properties from `:root`:**
```scss
.button {
  background-color: var(--button-background-color-primary);
  color: var(--button-text-color-primary);
  border-radius: var(--button-border-radius);
  padding: var(--button-padding);
  
  &--secondary {
    background-color: var(--button-background-color-secondary);
    color: var(--button-text-color-secondary);
  }
}
```

### SCSS Utilities

**No @use statements needed in components - all utilities are globally available:**

```scss
// Convert px to rem
font-size: to.rem(18px);

// Media queries
@include mq($from: tablet) {
  font-size: to.rem(24px);
}

@include mq($from: desktop) {
  font-size: to.rem(32px);
}

// Fluid typography/spacing (scales between breakpoints)
font-size: fluid(18px, 60px);
margin-bottom: fluid(16px, 32px);

// Content grid with gutters
.content {
  @include contentWithGutters;
}

// Responsive grid
.grid {
  --grid-column-count: 3;
  --grid-item--min-width: #{to.rem(200px)};
  @include contentGrid;
}
```

### Breakpoints

Available breakpoints (from `sass-mq`):
- `mobile` - 320px
- `tablet` - 768px
- `desktop` - 1024px
- `wide` - 1440px

### State Classes

Use `.is-*` for state classes:
- `.is-active`
- `.is-disabled`
- `.is-hidden`
- `.is-loading`
- `.is-flush` (no padding/margin)

---

## JavaScript Integration

### Drupal.behaviors Pattern

**Standard structure for all component JavaScript:**

```javascript
(function(Drupal) {
  'use strict';
  
  Drupal.behaviors.componentName = {
    attach(context) {
      // Use once() to prevent multiple initialization
      once('component-name', '.component-name', context).forEach((element) => {
        // Your component logic here
        
        // Example: Event listener
        const button = element.querySelector('.component-name__button');
        if (button) {
          button.addEventListener('click', (event) => {
            event.preventDefault();
            // Handle click
          });
        }
      });
    },
    
    detach(context, settings, trigger) {
      // Optional cleanup
      if (trigger === 'unload') {
        // Remove event listeners, etc.
      }
    }
  };
})(Drupal);
```

### Key JavaScript Patterns

**Always use once() to prevent duplicate initialization:**
```javascript
once('unique-id', '.selector', context).forEach((element) => {
  // Only runs once per element
});
```

**Common dependencies in component.yml:**
```yaml
dependencies:
  - core/once
  - core/drupal
```

**Accessibility considerations:**
```javascript
// Respect reduced motion preference
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (!prefersReducedMotion) {
  // Animate only if user hasn't requested reduced motion
}

// Manage focus
element.focus();
element.setAttribute('aria-expanded', 'true');
```

---

## Storybook Integration

### Story File Structure

**Create `.stories.twig` files for component documentation:**

```twig
{% stories component_name with { title: 'Components/Component Name' } %}
  
  {% story default with {
    name: 'Default',
    args: {
      text: 'Button Text',
      color: 'primary'
    }
  } %}
    {% include "four_starterkit:button" with {
      text: text,
      color: color
    } %}
  {% endstory %}
  
  {% story secondary with {
    name: 'Secondary',
    args: {
      text: 'Secondary Button',
      color: 'secondary'
    }
  } %}
    {% include "four_starterkit:button" with {
      text: text,
      color: color
    } %}
  {% endstory %}
  
  {% story with_icon with {
    name: 'With Icon',
    args: {
      text: 'Icon Button',
      icon: 'arrow'
    }
  } %}
    {% embed "four_starterkit:button" with {
      text: text
    } %}
      {% block icon %}
        <svg><!-- icon SVG --></svg>
      {% endblock %}
    {% endembed %}
  {% endstory %}
  
{% endstories %}
```

### Run Storybook

```bash
cd web/themes/custom/four_starterkit
pnpm run storybook  # Dev server on port 6006
pnpm run build-storybook  # Build static files
```

### Storybook Features

- **Accessibility testing** - Automated a11y checks with `@storybook/addon-a11y`
- **Responsive preview** - Test components at different viewports
- **Component isolation** - Test components without Drupal context
- **Documentation** - Auto-generated props documentation

---

## Component Composition

### When to Use Include vs Embed

**Use `include` for simple composition:**
- Passing props only
- No slot customization needed
- Reusing components as-is

```twig
{% include "four_starterkit:heading" with {
  tag: 'h2',
  text: title
} %}
```

**Use `embed` for slot customization:**
- Need to override blocks/slots
- Complex nested content
- Wrapping Drupal fields

```twig
{% embed "four_starterkit:card" %}
  {% block image %}
    {{ content.field_image }}
  {% endblock %}
  {% block content %}
    {{ content.body }}
  {% endblock %}
{% endembed %}
```

### Template Overrides (templates/ directory)

**Use templates/ to compose layouts with SDC components:**

**Example: `templates/content/node--article.html.twig`**
```twig
{% embed "four_starterkit:article" with {
  view_mode: view_mode,
  bundle: node.bundle
} %}
  {% block image %}
    {{ content.field_image }}
  {% endblock %}
  
  {% block header %}
    {% include "four_starterkit:heading" with {
      tag: 'h1',
      text: label
    } %}
  {% endblock %}
  
  {% block content %}
    {{ content.body }}
  {% endblock %}
{% endembed %}
```

**Key principle:** Templates should NOT contain styling - they only compose components.

---

## Common Patterns by Use Case

### Button Component

```yaml
# button.component.yml
props:
  type: object
  properties:
    text:
      type: string
      title: Button Text
    url:
      type: string
      title: Button URL
    color:
      type: string
      title: Color Variant
      enum: [primary, secondary, tertiary]
      default: primary
    attributes:
      type: Drupal\Core\Template\Attribute
```

```twig
{# button.twig #}
{% set attributes = attributes ?: create_attribute() %}
{% set attributes = attributes|without('class') %}
{% set classes = [
  'button',
  color ? 'button--' ~ color,
  class
] %}

<a href="{{ url }}" {{ attributes.addClass(classes) }}>
  {{ text }}
</a>
```

### Layout Component (Grid)

```yaml
# grid.component.yml
props:
  type: object
  required: [columns]
  properties:
    columns:
      type: string
      title: Number of Columns
      enum: ['2', '3', '4']
slots:
  content:
    title: Grid Content
```

```twig
{# grid.twig #}
{% set classes = [
  'grid',
  columns ? 'grid--columns-' ~ columns,
  class
] %}

<div {{ attributes.addClass(classes) }}>
  {% block content %}{% endblock %}
</div>
```

```scss
// grid.scss
.grid {
  display: grid;
  gap: var(--grid-gap);
  
  &--columns-2 {
    grid-template-columns: repeat(2, 1fr);
  }
  
  &--columns-3 {
    grid-template-columns: repeat(3, 1fr);
  }
  
  &--columns-4 {
    grid-template-columns: repeat(4, 1fr);
  }
}
```

### Content Wrapper (Card)

```yaml
# card.component.yml
slots:
  image:
    title: Card Image
  content:
    title: Card Content
```

```twig
{# card.twig #}
<article class="card">
  {% block image %}{% endblock %}
  <div class="card__content">
    {% block content %}{% endblock %}
  </div>
</article>
```

### Utility Component (Visually Hidden)

```yaml
# visually_hidden.component.yml
name: Visually Hidden
description: Hides content visually but keeps it accessible to screen readers
slots:
  content:
    title: Content to hide visually
```

```twig
{# visually_hidden.twig #}
<span class="visually-hidden">
  {% block content %}{% endblock %}
</span>
```

**Usage for icon buttons:**
```twig
<button class="button button--icon">
  <svg><!-- Icon --></svg>
  {% include "four_starterkit:visually_hidden" with {
    content: 'Close menu'
  } %}
</button>
```

---

## Accessibility Requirements

**This project must meet WCAG 2.2 AA standards.**

### Required Patterns

**1. Visually hidden text for icon buttons:**
```twig
<button class="button button--icon">
  <svg aria-hidden="true"><!-- Icon --></svg>
  {% include "four_starterkit:visually_hidden" with {
    content: 'Descriptive button text'
  } %}
</button>
```

**2. Native HTML elements (preferred):**
- Accordions: Use `<details>` and `<summary>`
- Modals: Use `<dialog>` element
- Buttons: Use `<button>`, not `<div>` with click handlers

**3. Focus states:**
```scss
.button {
  &:focus-visible {
    outline: 2px solid var(--color-focus);
    outline-offset: 2px;
  }
}
```

**4. Reduced motion:**
```scss
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

```javascript
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
```

**5. ARIA attributes when needed:**
```twig
<button
  aria-expanded="{{ is_open ? 'true' : 'false' }}"
  aria-controls="menu-{{ id }}"
  aria-label="Toggle menu"
>
```

---

## Troubleshooting

### Component not appearing
- Check component is in `components/` directory
- Verify component.yml schema is valid YAML
- Clear cache: `drush cr`
- Check for Twig syntax errors in template

### Styles not applying
- Verify `.scss` file matches component machine name
- Check libraryOverrides in component.yml includes the SCSS file
- Ensure BEM class names match between Twig and SCSS
- Clear cache and rebuild: `drush cr && pnpm run build`

### Props not passing through
- Check prop names match exactly in component.yml and Twig
- Verify prop types in schema match data types being passed
- Use `{% dump(props) %}` in Twig to debug (remove after)

### Storybook not rendering
- Verify stories file matches pattern: `{name}.stories.twig`
- Check server URL in stories.json points to correct domain
- Ensure Drupal site is accessible at configured URL
- Rebuild Storybook: `pnpm run build-storybook`

---

## Build Commands

```bash
# Theme development
cd web/themes/custom/four_starterkit
pnpm install          # Install dependencies
pnpm run dev          # Development mode with watch
pnpm run build        # Production build

# Storybook
pnpm run storybook    # Dev server (http://localhost:6006)
pnpm run build-storybook  # Build static files

# Drupal
drush cr              # Clear cache
drush cex             # Export configuration
drush cim             # Import configuration
```

---

## Security Note

**Never commit sensitive data in component schemas or config exports.**
Check for API keys, tokens, or credentials before committing component.yml files.
