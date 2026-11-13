import { defineConfig } from 'vitest/config';

// Kept separate from vite.config.mjs so the theme's build pipeline, its Sass
// injection, and the Sentry plugin play no part in the JavaScript tests.
export default defineConfig({
  test: {
    environment: 'jsdom',
    include: ['tests/js/**/*.test.js'],
  },
});
