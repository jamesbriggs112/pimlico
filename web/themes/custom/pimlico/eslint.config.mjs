import globals from 'globals';
import { defineConfig } from 'eslint/config';
import storybook from 'eslint-plugin-storybook';

export default defineConfig(...storybook.configs['flat/recommended'], [
  {
    files: ['**/*.{js,mjs,cjs}'],
    languageOptions: { globals: globals.browser },
  },
]);
