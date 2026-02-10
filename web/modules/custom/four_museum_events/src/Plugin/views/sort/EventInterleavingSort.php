<?php

namespace Drupal\four_museum_events\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler for event interleaving pattern.
 *
 * Sorts events in a repeating pattern: G-T-T-P-P-T-T-P-T-P-T-T-P-P-T-P
 * where:
 *  - G = Museum events (event_type: museum)
 *  - T = Temporary events (event_type: one_off, limited_recurring, exhibition)
 *  - P = Permanent events (event_type: recurring, gallery)
 *
 * Within each group, events are sorted:
 *  - T: by field_start_date ASC (soonest first)
 *  - P: by created ASC (oldest first)
 *  - G: by created ASC (oldest first)
 *
 * @ViewsSort("event_interleaving_sort")
 */
class EventInterleavingSort extends SortPluginBase
{

  /**
   * The interleaving pattern.
   *
   * Defines the repeating 16-position cycle.
   */
  const PATTERN = ['G', 'T', 'T', 'P', 'P', 'T', 'T', 'P', 'T', 'P', 'T', 'T', 'P', 'P', 'T', 'P'];

  /**
   * {@inheritdoc}
   */
  public function query()
  {
    $this->ensureMyTable();

    // Build the pattern position mappings.
    $g_positions = [];
    $t_positions = [];
    $p_positions = [];

    foreach (self::PATTERN as $index => $type) {
      if ($type === 'G') {
        $g_positions[] = $index;
      } elseif ($type === 'T') {
        $t_positions[] = $index;
      } elseif ($type === 'P') {
        $p_positions[] = $index;
      }
    }

    // Join the event type field table if not already joined.
    $this->query->ensureTable('node__field_event_type', $this->relationship);
    $this->query->ensureTable('node__field_start_date', $this->relationship);

    // Build the SQL expression using window functions (MySQL 8.0+).
    // Calculates a sort weight for each event based on pattern position.
    $formula = "
      (CASE
        -- Museum events (G)
        WHEN node__field_event_type.field_event_type_value = 'museum' THEN
          (
            (ROW_NUMBER() OVER (
              PARTITION BY CASE WHEN node__field_event_type.field_event_type_value = 'museum' THEN 1 ELSE 0 END
              ORDER BY node_field_data.created ASC
            ) - 1) * 16
          ) + 0

        -- Temporary events (T): one_off, limited_recurring, exhibition
        WHEN node__field_event_type.field_event_type_value IN ('one_off', 'limited_recurring', 'exhibition') THEN
          (
            FLOOR(
              (ROW_NUMBER() OVER (
                PARTITION BY CASE WHEN node__field_event_type.field_event_type_value IN ('one_off', 'limited_recurring', 'exhibition') THEN 1 ELSE 0 END
                ORDER BY COALESCE(node__field_start_date.field_start_date_value, '9999-12-31') ASC, node_field_data.created ASC
              ) - 1) / " . count($t_positions) . "
            ) * 16
          ) +
          ELT(
            ((ROW_NUMBER() OVER (
              PARTITION BY CASE WHEN node__field_event_type.field_event_type_value IN ('one_off', 'limited_recurring', 'exhibition') THEN 1 ELSE 0 END
              ORDER BY COALESCE(node__field_start_date.field_start_date_value, '9999-12-31') ASC, node_field_data.created ASC
            ) - 1) % " . count($t_positions) . ") + 1,
            " . implode(', ', $t_positions) . "
          )

        -- Permanent events (P): recurring, gallery
        WHEN node__field_event_type.field_event_type_value IN ('recurring', 'gallery') THEN
          (
            FLOOR(
              (ROW_NUMBER() OVER (
                PARTITION BY CASE WHEN node__field_event_type.field_event_type_value IN ('recurring', 'gallery') THEN 1 ELSE 0 END
                ORDER BY node_field_data.created ASC
              ) - 1) / " . count($p_positions) . "
            ) * 16
          ) +
          ELT(
            ((ROW_NUMBER() OVER (
              PARTITION BY CASE WHEN node__field_event_type.field_event_type_value IN ('recurring', 'gallery') THEN 1 ELSE 0 END
              ORDER BY node_field_data.created ASC
            ) - 1) % " . count($p_positions) . ") + 1,
            " . implode(', ', $p_positions) . "
          )

        -- Fallback for unknown types
        ELSE 999999
      END)
    ";

    // Add the computed field as the sort order.
    $this->query->addOrderBy(NULL, $formula, $this->options['order'], 'event_interleaving_weight');
  }
}
