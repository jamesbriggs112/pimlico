# Four Museum Events

Custom Drupal module providing an intelligent event sorting algorithm that interleaves museum, temporary, and permanent events in a repeating pattern.

## Overview

This module provides a custom Views sort handler that replaces the inefficient multi-query WordPress approach with a single optimized database query using MySQL window functions.

## The Interleaving Pattern

Events are displayed in a repeating 16-position cycle:

```
G - T - T - P - P - T - T - P - T - P - T - T - P - P - T - P
```

Where:

- **G** = Museum events (`field_event_type`: `museum`)
- **T** = Temporary events (`field_event_type`: `one_off`, `limited_recurring`, `exhibition`)
- **P** = Permanent events (`field_event_type`: `recurring`, `gallery`)

### Visual Representation

```
Position:  0   1   2   3   4   5   6   7   8   9  10  11  12  13  14  15
Pattern:   G   T   T   P   P   T   T   P   T   P   T   T   P   P   T   P
           │   │   │   │   │   │   │   │   │   │   │   │   │   │   │   │
Event:     M1  T1  T2  P1  P2  T3  T4  P3  T5  P4  T6  T7  P5  P6  T8  P7
           M2  T9  T10 P8  P9  T11 T12 P10 T13 P11 T14 T15 P12 P13 T16 P14
           ...continues repeating...
```

## Within-Group Sorting

Events are sorted within their groups:

- **Temporary (T)**: Sorted by `field_start_date` ASC (soonest events first)
- **Permanent (P)**: Sorted by `created` ASC (oldest posts first)
- **Museum (G)**: Sorted by `created` ASC (oldest posts first)

This ensures that within each T slot, you see the most urgent/upcoming events first, while permanent events maintain a stable chronological order.

## Requirements

### System Requirements

- **Drupal**: 10.x or 11.x
- **MySQL**: 8.0+ or **MariaDB**: 10.2+ (for window function support)

### Content Type Requirements

The module expects an `event` content type with these fields:

- `field_event_type` (list_string, required) - Allowed values:
  - `museum` - Museum events (G)
  - `one_off` - One-off temporary events (T)
  - `limited_recurring` - Limited recurring events (T)
  - `exhibition` - Exhibition events (T)
  - `recurring` - Recurring permanent events (P)
  - `gallery` - Gallery permanent events (P)
- `field_start_date` (datetime) - Used for sorting T events
- `created` (timestamp) - Used for sorting P and G events

## Implementation Details

### Database Performance

The module uses MySQL 8.0+ window functions (specifically `ROW_NUMBER() OVER (PARTITION BY ... ORDER BY ...)`) to calculate event positions efficiently within a single query. This approach:

1. **Calculates row numbers** within each event group (G, T, P)
2. **Maps row numbers to pattern positions** using the 16-slot repeating cycle
3. **Applies at SQL level** before pagination, ensuring correct ordering across all pages

### SQL Strategy

For each event, the sort plugin calculates a weight using:

```sql
weight = (cycle_number * 16) + position_in_cycle
```

Where:

- `cycle_number` = `FLOOR(row_number_in_group / slots_for_group)`
- `position_in_cycle` = Pattern position for this group at this row number
- Groups have different slot counts (G=1, T=8, P=7 per 16-position cycle)

## Usage

### Enabling the Module

```bash
drush en four_museum_events -y
drush cr
```

### Using in Views

The sort handler is automatically available in Views:

1. Edit your Events view: `/admin/structure/views/view/events`
2. Add sort criteria
3. Select "Event Interleaving Pattern"
4. Set order to "Ascending"
5. Save the view

The module is already configured in the default Events view.

### Pagination

The sort handler works correctly with pagination because sorting happens at the SQL level before `LIMIT`/`OFFSET` is applied. Events will maintain the correct interleaving pattern across all pages.

## Modifying the Pattern

To change the interleaving pattern, edit the `PATTERN` constant in:

```
web/modules/custom/four_museum_events/src/Plugin/views/sort/EventInterleavingSort.php
```

```php
const PATTERN = ['G', 'T', 'T', 'P', 'P', 'T', 'T', 'P', 'T', 'P', 'T', 'T', 'P', 'P', 'T', 'P'];
```

After changing the pattern:

1. Clear caches: `drush cr`
2. The new pattern takes effect immediately

### Pattern Guidelines

- Use **G** for museum events
- Use **T** for temporary events
- Use **P** for permanent events
- The pattern can be any length (not limited to 16)
- Each type should appear at least once for best results
- More slots for T means more temporary events will be prioritized

## Testing

### Verify MySQL Version

```bash
drush sql:query "SELECT VERSION()"
```

Ensure output shows MySQL 8.0+ or MariaDB 10.2+.

### Create Test Events

For proper testing, create:

- 5+ museum events (type: `museum`)
- 15+ temporary events (types: `one_off`, `limited_recurring`, `exhibition`) with varied start dates
- 10+ permanent events (types: `recurring`, `gallery`)

### Verify Pattern

Visit `/events` and check:

1. First page follows G-T-T-P-P-T-T-P-T-P-T-T-P-P-T-P pattern
2. Pagination continues pattern correctly
3. Temporary events show chronologically (earliest start dates in earlier T slots)
4. Permanent events show oldest first

### Query Inspection

View the generated SQL query:

```bash
drush views:execute events --show-query
```

Look for `ROW_NUMBER() OVER` to confirm window functions are being used.

## Troubleshooting

### MySQL Version Too Old

**Error**: Syntax error near `ROW_NUMBER()`

**Solution**: Upgrade to MySQL 8.0+ or MariaDB 10.2+. Check version:

```bash
drush sql:query "SELECT VERSION()"
```

### Events Not Appearing in Pattern

**Check**:

1. Verify `field_event_type` values match expected values exactly
2. Ensure view filters aren't excluding events
3. Check that events meet the view's date filter criteria
4. Clear all caches: `drush cr`

### Pattern Breaks Across Pages

**This should not happen** if the module is working correctly. If it does:

1. Verify module is enabled: `drush pm:list | grep four_museum_events`
2. Check the view is using the sort handler (not a different sort)
3. Look for errors in watchdog: `drush watchdog:show`

## Architecture Notes

### Why This Approach?

The original WordPress implementation used 5+ separate database queries and manual array merging in PHP. This approach:

- ❌ Doesn't work with pagination (sorting happens after query limit)
- ❌ Inefficient (multiple queries vs one)
- ❌ Memory-intensive for large datasets
- ❌ Harder to maintain

The Drupal implementation:

- ✅ Single optimized query with window functions
- ✅ Pagination-compatible (sorting at SQL level)
- ✅ Efficient for large datasets
- ✅ Maintainable with clear pattern constant
- ✅ Leverages MySQL 8.0 modern features

### Future Enhancements

Possible improvements:

- Add admin UI to configure pattern without code changes
- Support for additional event type groupings
- Expose pattern configuration in Views UI
- Add drush command to preview pattern for current event set

## License

GPL-2.0+

## Maintainer

Four Agency
