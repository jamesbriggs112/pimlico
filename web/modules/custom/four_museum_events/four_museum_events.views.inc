<?php

/**
 * @file
 * Provide views data for four_museum_events module.
 */

/**
 * Implements hook_views_data().
 */
function four_museum_events_views_data()
{
  $data = [];

  // Add a custom sort handler to the node_field_data table.
  $data['node_field_data']['event_interleaving_sort'] = [
    'title' => t('Event Interleaving Pattern'),
    'help' => t('Sorts events in a repeating pattern of museum, temporary, and permanent events (G-T-T-P-P-T-T-P-T-P-T-T-P-P-T-P).'),
    'sort' => [
      'id' => 'event_interleaving_sort',
    ],
  ];

  return $data;
}
