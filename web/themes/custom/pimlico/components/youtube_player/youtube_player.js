/**
 * @file
 * YouTube player: swaps the poster for the video when someone presses play.
 *
 * Until then nothing is requested from YouTube (no cookies, no third-party
 * scripts). The video then plays in Plyr, with the site's player controls,
 * from youtube-nocookie.com. Without Plyr it falls back to YouTube's own
 * embed; without JavaScript the poster is a link to the video on YouTube.
 */
/* global Plyr */
((Drupal, once) => {
  const embed = (id, start, title) => {
    const params = new URLSearchParams({
      autoplay: '1',
      rel: '0',
      modestbranding: '1',
      playsinline: '1',
      start: String(start),
    });
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube-nocookie.com/embed/${id}?${params}`;
    iframe.title = title;
    iframe.allow =
      'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
    iframe.allowFullscreen = true;
    return iframe;
  };

  Drupal.behaviors.youtubePlayer = {
    attach(context) {
      once('youtube-player', '[data-youtube-player]', context).forEach(
        (figure) => {
          const facade = figure.querySelector('[data-youtube-play]');
          const frame = figure.querySelector('.youtube-player__frame');
          const id = figure.dataset.youtubePlayer;
          const start = parseInt(figure.dataset.start, 10) || 0;
          const { title } = figure.dataset;

          facade.addEventListener('click', (event) => {
            event.preventDefault();
            figure.classList.add('youtube-player--playing');

            if (typeof Plyr === 'undefined') {
              const iframe = embed(id, start, title);
              facade.replaceWith(iframe);
              iframe.focus();
              return;
            }

            const target = document.createElement('div');
            target.dataset.plyrProvider = 'youtube';
            target.dataset.plyrEmbedId = id;
            facade.replaceWith(target);
            const player = new Plyr(target, {
              autoplay: true,
              iconUrl: '/libraries/plyr/dist/plyr.svg',
              title,
              youtube: {
                noCookie: true,
                rel: 0,
                modestbranding: 1,
                iv_load_policy: 3,
                playsinline: 1,
                start,
              },
              i18n: {
                play: Drupal.t('Play'),
                pause: Drupal.t('Pause'),
                mute: Drupal.t('Mute'),
                unmute: Drupal.t('Unmute'),
                enterFullscreen: Drupal.t('Enter full screen'),
                exitFullscreen: Drupal.t('Exit full screen'),
                frameTitle: Drupal.t('Player for @title', { '@title': title }),
              },
            });
            player.on('ready', () => {
              player.play();
              const button = frame.querySelector('.plyr__controls button');
              if (button) {
                button.focus();
              }
            });
          });
        }
      );
    },
  };
})(Drupal, once);
