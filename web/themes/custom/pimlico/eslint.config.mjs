import js from '@eslint/js';
import globals from 'globals';
import { defineConfig } from 'eslint/config';
import storybook from 'eslint-plugin-storybook';

export default defineConfig(...storybook.configs['flat/recommended'], [
  js.configs.recommended,
  {
    files: ['**/*.{js,mjs,cjs}'],
    languageOptions: {
      globals: { ...globals.browser, Drupal: 'readonly', once: 'readonly' },
    },
  },
  {
    files: ['*.{js,mjs}', 'tests/**/*.js', '.storybook/**/*.js'],
    languageOptions: { globals: globals.node },
  },
]);
