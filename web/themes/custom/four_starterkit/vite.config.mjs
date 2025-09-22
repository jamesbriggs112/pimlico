import { defineConfig } from 'vite';
import multiInput from 'rollup-plugin-multi-input';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';
import { sentryVitePlugin } from '@sentry/vite-plugin';

const currentDir = dirname(fileURLToPath(import.meta.url));
console.log(`@use "${join(currentDir, './src/mixins.scss')}" as *`);

export default defineConfig({
  plugins: [
    multiInput(),
    sentryVitePlugin({
      org: process.env.SENTRY_ORG,
      project: process.env.SENTRY_PROJECT,
      authToken: process.env.SENTRY_AUTH_TOKEN,
    }),
  ],
  server: {
    cors: true,
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
        additionalData: `
        @use 'sass-rem' as to;
        @use '${join(currentDir, './src/sass/init/mq-settings')}';
        @use 'sass-mq/mq' as *;
        @use '${join(currentDir, './src/sass/init/mixins')}' as *;
        `,
      },
    },
  },
  build: {
    manifest: true,
    sourcemap: true,
    rollupOptions: {
      input: [
        'src/sass/styles.scss',
        'src/sass/hamburgers/hamburgers.scss',
        'src/js/site.js',
        '!src/sass/components/_*.scss',
        'src/sass/components/*.scss',
        '!src/js/components/_*.js',
        'src/js/components/*.js',
        'src/js/header/*.js',
      ],
      output: {
        assetFileNames: ({ name }) => {
          if (/\.css$/.test(name ?? '')) {
            name = name.replace('sass', 'css');
            return 'assets/' + name + '-[hash][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
  },
});
