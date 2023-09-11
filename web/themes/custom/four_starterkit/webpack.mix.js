const mix = require('laravel-mix');
const fs = require('fs');

function findFiles(dir) {
  return fs.readdirSync(dir).filter((file) => {
    return fs.statSync(`${dir}/${file}`).isFile();
  });
}

function buildSass(dir, dest) {
  findFiles(dir).forEach((file) => {
    if (!file.startsWith('_')) {
      mix.sass(dir + '/' + file, dest);
    }
  });
}

function buildJs(dir, dest) {
  findFiles(dir).forEach((file) => {
    if (!file.startsWith('_')) {
      mix.js(dir + '/' + file, dest).sourceMaps(false, 'source-map');
    }
  });
}

mix.webpackConfig({ devtool: 'source-map' });
mix.disableNotifications();

// Global
mix
  .js('src/js/site.js', 'js/')
  .sourceMaps(false, 'source-map')
  .sass('src/sass/styles.scss', 'css/');

// Components (You will need to make sure these are added in your libraries.yml)
buildSass('src/sass/components', 'css/components');
buildJs('src/js/components', 'js/components');

mix.options({
  processCssUrls: false,
});

mix.browserSync({
  proxy: process.env.SITE_DOMAIN,
  files: ['css/**/*.css', 'js/**/*.js'],
});
