<?php

/**
 * @file
 * Collects each Brand's own photos from its belgraviavillage.com page.
 *
 *   Php scripts/fetch-photos.php.
 *
 * Reads data/brands.json (source_url per Brand), fetches each page (half a
 * second apart), and takes the images in the page's banner at their widest
 * size. Writes data/photos.json; nothing is
 * downloaded here (scripts/import-photos.php does that).
 */

declare(strict_types=1);

$dir = dirname(__DIR__) . '/data';
$brands = json_decode((string) file_get_contents("$dir/brands.json"), TRUE, flags: JSON_THROW_ON_ERROR)['brands'];
$context = stream_context_create([
  'http' => [
    'header' => "User-Agent: Mozilla/5.0 (compatible; pimlico-map/1.0)\r\n",
    'timeout' => 20,
  ],
]);

$out = [];
foreach ($brands as $brand) {
  $url = $brand['source_url'] ?? '';
  if (!$url) {
    continue;
  }
  $html = @file_get_contents($url, FALSE, $context);
  usleep(500000);
  if (!$html) {
    fwrite(STDERR, "{$brand['id']}: could not fetch $url\n");
    continue;
  }
  $base = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

  // The banner of the full Brand node, up to the next section.
  $start = strpos($html, 'node--view-mode-full');
  $banner = $start === FALSE ? FALSE : strpos($html, 'banner', $start);
  $photos = [];
  if ($banner !== FALSE) {
    $end = strpos($html, 'field--name-field-description', $banner) ?: $banner + 20000;
    // Each image at its widest size: the largest srcset candidate (a 1920px
    // WebP), or src for carousel slides without one. The original uploads
    // are 2.5-6 MB each, so they're not used.
    preg_match_all('/<img\b[^>]*>/', substr($html, $banner, $end - $banner), $tags);
    foreach ($tags[0] as $tag) {
      $best = ['', 0];
      if (preg_match('/\ssrcset="([^"]+)"/', $tag, $set)) {
        foreach (explode(',', html_entity_decode($set[1])) as $candidate) {
          [$src, $width] = array_pad(preg_split('/\s+/', trim($candidate)), 2, '0w');
          if ((float) $width > $best[1]) {
            $best = [$src, (float) $width];
          }
        }
      }
      if (!$best[0] && preg_match('/\ssrc="([^"]+)"/', $tag, $one)) {
        $best = [html_entity_decode($one[1]), 1];
      }
      if ($best[0] && !str_contains($best[0], '.svg')) {
        $photos[] = $base . $best[0];
      }
    }
  }
  preg_match_all('#href="(https?://(?:www\.)?instagram\.com/(?!belgraviavillage)[^"/?]+)#', $html, $ig);
  $out[$brand['id']] = [
    'photos' => array_values(array_unique($photos)),
    'instagram' => $ig[1][0] ?? '',
  ];
  printf("%-40s %d photo(s)\n", $brand['id'], count($out[$brand['id']]['photos']));
}

$json = json_encode([
  'source' => 'belgraviavillage.com',
  'fetched' => date('c'),
  'brands' => $out,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents("$dir/photos.json", $json . "\n");
printf("%d Brands, %d photos → data/photos.json\n", count($out), array_sum(array_map(static fn($b) => count($b['photos']), $out)));
