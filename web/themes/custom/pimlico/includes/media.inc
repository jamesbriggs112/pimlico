<?php

/**
 * @file
 * Preprocess functions for media.
 */

declare(strict_types=1);

/**
 * Implements hook_preprocess_HOOK() for media--remote-video.
 *
 * YouTube links render with pimlico:youtube_player: this finds the video ID
 * and start time in the URL, and renders the oEmbed thumbnail (stored on
 * this site) as the poster, so nothing loads from YouTube before play.
 */
function pimlico_preprocess_media__remote_video(array &$variables): void {
  $media = $variables['media'];
  $url = (string) ($media->get('field_media_oembed_video')->value ?? '');
  $video = _pimlico_youtube_video($url);
  if (!$video) {
    return;
  }
  $video['title'] = $media->label();
  $video['poster'] = $media->get('thumbnail')->view([
    'type' => 'responsive_image',
    'label' => 'hidden',
    'settings' => [
      'responsive_image_style' => 'natural',
      'image_link' => '',
      'image_loading' => ['attribute' => 'lazy'],
    ],
  ]);
  $variables['youtube'] = $video;
}

/**
 * Returns a YouTube URL's video ID and start time, or NULL for other URLs.
 *
 * Handles youtube.com/watch?v=, youtu.be/, /embed/, /shorts/ and /live/
 * links, with a start time in t= (90, 1m30s) or start=.
 *
 * @return array{video_id: string, start: int}|null
 *   The video ID and start time in seconds.
 */
function _pimlico_youtube_video(string $url): ?array {
  $parts = parse_url($url);
  $host = preg_replace('/^(www\.|m\.)/', '', strtolower($parts['host'] ?? ''));
  parse_str($parts['query'] ?? '', $query);
  $path = $parts['path'] ?? '';

  $id = match (TRUE) {
    $host === 'youtu.be' => ltrim($path, '/'),
    in_array($host, ['youtube.com', 'youtube-nocookie.com'], TRUE) && isset($query['v']) => (string) $query['v'],
    in_array($host, ['youtube.com', 'youtube-nocookie.com'], TRUE) && (bool) preg_match('#^/(embed|shorts|live)/([^/?]+)#', $path, $m) => $m[2],
    default => '',
  };
  if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
    return NULL;
  }

  $start = 0;
  $time = (string) ($query['t'] ?? $query['start'] ?? '');
  if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s?)?$/', $time, $t)) {
    $start = ((int) ($t[1] ?? 0)) * 3600 + ((int) ($t[2] ?? 0)) * 60 + (int) ($t[3] ?? 0);
  }
  return ['video_id' => $id, 'start' => $start];
}
