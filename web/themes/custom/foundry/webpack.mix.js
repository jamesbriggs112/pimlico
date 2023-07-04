const mix = require('laravel-mix');

mix.disableNotifications();

// Global
mix.js('src/js/site.js', 'js/').sass('sass/styles.scss', 'css/');

// Components
mix.js('src/js/components/paragraph-accordion.js', 'js/components/').sass('sass/components/paragraph-accordion.scss', 'css/components');
mix.js('src/js/components/paragraph-image-carousel.js', 'js/components/').sass('sass/components/paragraph-image-carousel.scss', 'css/components');;
mix.options({
  processCssUrls: false,
});
mix.sass('sass/components/paragraph-iframe-embed.scss', 'css/components');