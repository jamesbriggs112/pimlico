# Research-first workflow

Before adding custom code, verify whether an existing Drupal solution already exists.

## Default approach

1. Ask whether the team has checked drupal.org for an existing contrib module.
2. Offer to research before building anything custom.
3. Only implement custom code once no suitable contrib module, recipe, or patchable solution is found.

## How to research contrib modules

Search on drupal.org/project/project_module and evaluate module health by checking:

- Drupal 10/11 compatibility
- Security coverage and maintenance status
- Last commit date and issue queue responsiveness
- Number of sites using it
- Whether the module is covered by Drupal security or maintained by a trusted team

## Questions to ask

- Is there a well-maintained contrib module for this?
- Can an existing module be extended rather than rebuilt?
- Is there a Drupal recipe (10.3+) that bundles this functionality?
- Would a patch to an existing module be better than custom code?

## Example research prompt

```markdown
## Task

Create a block that shows recent articles.

## Requirements

- Use an existing contrib module if possible.
- Prefer a recipe or patchable solution over custom code.
- Document why any custom implementation is necessary.
```
