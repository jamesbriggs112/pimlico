// Compiles every SCSS entry point the way vite.config.mjs does, into a
// directory of your choice and without touching dist/, so the compiled CSS
// can be compared before and after a change with compare-css.mjs.
//
//   node scripts/qa/compile-css.mjs /tmp/css-before
//
// Run from the project root.
import { createRequire } from 'node:module';
import { globSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

const outDir = process.argv[2];
if (!outDir) {
  console.error('Usage: node scripts/qa/compile-css.mjs <out-dir>');
  process.exit(1);
}
const themeDir = resolve('web/themes/custom/pimlico');
const require = createRequire(join(themeDir, 'package.json'));
const sass = require('sass');

// The same lines vite.config.mjs prepends to every stylesheet.
const prelude = [
  `@use 'sass-rem' as to;`,
  `@use '${join(themeDir, 'src/sass/init/mq-settings')}';`,
  `@use 'sass-mq/mq' as *;`,
  `@use '${join(themeDir, 'src/sass/init/mixins')}' as *;`,
].join('\n');

// Vite resolves bare package names (`@use 'sass-mq'`) through each
// package's package.json; plain Sass doesn't.
const packageImporter = {
  findFileUrl(url) {
    if (/^(\.|\/|file:)/.test(url)) {
      return null;
    }
    const name = url.split('/').slice(0, url.startsWith('@') ? 2 : 1).join('/');
    let manifest;
    try {
      manifest = require.resolve(`${name}/package.json`);
    } catch {
      return null;
    }
    const base = dirname(manifest);
    const file = url === name ? require(manifest).main : url.slice(name.length + 1);
    return pathToFileURL(join(base, file));
  },
};

const entries = globSync(['src/sass/styles.scss', 'components/**/*.scss'], {
  cwd: themeDir,
  exclude: ['src/sass/**/_*.scss', 'components/**/_*.scss'],
});
for (const entry of entries) {
  const file = join(themeDir, entry);
  const { css } = sass.compileString(`${prelude}\n${readFileSync(file, 'utf8')}`, {
    url: pathToFileURL(file),
    importers: [packageImporter],
    style: 'expanded',
    silenceDeprecations: ['import'],
  });
  const target = join(outDir, entry.replace(/\.scss$/, '.css'));
  mkdirSync(dirname(target), { recursive: true });
  writeFileSync(target, css);
}
console.log(`${entries.length} stylesheets compiled into ${outDir}`);
