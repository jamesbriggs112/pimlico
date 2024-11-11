import { defineConfig } from 'vite';
import multiInput from 'rollup-plugin-multi-input';

export default defineConfig({
  plugins: [multiInput()],
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `
        @use 'sass-rem' as to;
        @use 'src/sass/init/mq-settings';
        @use 'sass-mq/mq' as *;
        @use 'src/sass/init/mixins' as *;
        `,
      },
    },
  },
  build: {
    manifest: true,
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
