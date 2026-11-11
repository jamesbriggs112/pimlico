import { defineConfig } from 'vite';
import { globSync } from 'node:fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';
import { sentryVitePlugin } from '@sentry/vite-plugin';

const currentDir = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [
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
      input: globSync(
        [
          'src/sass/styles.scss',
          'src/js/**/*.js',
          'components/**/*.scss',
          'components/**/*.js',
        ],
        {
          cwd: currentDir,
          exclude: ['src/sass/**/_*.scss', 'components/**/_*.scss'],
        }
      ),
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
