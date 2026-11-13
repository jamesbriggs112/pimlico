<?php

/**
 * @file
 * Builds data/brands.json, the migration source, from the map project.
 *
 * The map project's data/processed/map-source.json holds each Brand as
 * imported from belgraviavillage.com, with its placed Map Building. This
 * splits the free-text addresses into address parts, a Unit name and an
 * Area, so the migration YAML needs no address parsing.
 *
 *   php scripts/build-source.php ../../../../../pimlico-map
 */

declare(strict_types=1);

$repo = $argv[1] ?? dirname(__DIR__, 6) . '/pimlico-map';
$source = json_decode((string) file_get_contents($repo . '/data/processed/map-source.json'), TRUE, flags: JSON_THROW_ON_ERROR);
$feed = json_decode((string) file_get_contents($repo . '/output/map/brands.json'), TRUE, flags: JSON_THROW_ON_ERROR);
$buildings = array_column($feed['brands'], 'buildings', 'id');

// Newson's Yard is building w544484759 (split into units by the map).
$yard = 'w544484759';

$normalise = static fn(string $s): string => trim(preg_replace(
  ['/\bSt\b\.?(?=\s*(,|$))/', '/\bRd\b\.?/', '/\s+/', '/Newsons Yard/i'],
  ['Street', 'Road', ' ', 'Newson’s Yard'],
  $s,
));

$brands = [];
foreach ($source['brands'] as $brand) {
  $address = $normalise((string) ($brand['address'] ?? ''));
  $postcode = '';
  if (preg_match('/\b(SW1W?\s?\d[A-Z]{2})\b/i', $address, $m)) {
    // Every postcode here is SW1W; the source has one "SW1 8LP".
    $postcode = preg_replace('/^SW1\s/', 'SW1W ', strtoupper($m[1]));
    $address = trim(str_replace($m[0], '', $address), ' ,');
  }
  // Parts, without the city or a leading trading name ("OCHRE, 22-24 …").
  $parts = array_values(array_filter(array_map('trim', explode(',', $address)), static fn($p) => $p !== '' && strcasecmp($p, 'London') !== 0));
  if (count($parts) > 1 && !preg_match('/\d|Newson/', $parts[0])) {
    array_shift($parts);
  }
  // "Newson’s Yard 57" → "Newson’s Yard", "57 Pimlico Road".
  $parts = array_map(static fn($p) => preg_replace('/^Newson’s Yard (\d+)$/', 'Newson’s Yard', $p), $parts);
  $ids = $buildings[$brand['id']] ?? [$brand['building_id']];
  $in_yard = str_starts_with($ids[0], $yard) || preg_grep('/Newson/', $parts);
  if ($in_yard && !preg_grep('/Pimlico Road/', $parts)) {
    $parts[] = '57 Pimlico Road';
  }
  $line1 = $parts[0] ?? $brand['name'];
  $line2 = $parts[1] ?? '';
  if ($in_yard && !str_contains($line1 . $line2, 'Newson')) {
    // Placed in the Yard by override while the source address says otherwise.
    [$line1, $line2] = ['Newson’s Yard', '57 Pimlico Road'];
  }
  if ($in_yard && $line2 === 'Newson’s Yard') {
    // "Unit 7", "Newson’s Yard" → "Unit 7, Newson’s Yard", "57 Pimlico Road".
    [$line1, $line2] = [$line1 . ', Newson’s Yard', '57 Pimlico Road'];
  }
  if ($in_yard && !preg_match('/\d/', $line2)) {
    $line2 = '57 Pimlico Road';
  }
  if ($in_yard && $postcode === '') {
    $postcode = 'SW1W 8NE';
  }
  $street = $line2 ?: $line1;
  $area = match (TRUE) {
    $in_yard => 'Newson\'s Yard',
    str_contains($street, 'Ebury') => 'Ebury Street',
    str_contains($street, 'Bourne') => 'Bourne Street',
    str_contains($street, 'Holbein') => 'Holbein Place',
    default => 'Pimlico Road',
  };
  $unit = $in_yard && $line1 === 'Newson’s Yard' ? 'Newson’s Yard (' . $brand['name'] . ')' : $line1;

  $brands[] = [
    'id' => $brand['id'],
    'name' => $brand['name'],
    'category' => $brand['category'],
    'summary' => (string) ($brand['short_description'] ?? ''),
    'website' => (string) ($brand['website'] ?? ''),
    'opening_hours' => implode("\n", $brand['opening_hours'] ?? []),
    'unit' => $unit,
    'address_line1' => $line1,
    'address_line2' => $line2,
    'postal_code' => $postcode,
    'area' => $area,
    'buildings' => $ids,
    'placed_by' => $brand['placed_by'] ?? '',
    'source_url' => (string) ($brand['url'] ?? ''),
  ];
}

$out = dirname(__DIR__) . '/data/brands.json';
file_put_contents($out, json_encode(['brands' => $brands], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
printf("%d brands written to %s\n", count($brands), $out);
