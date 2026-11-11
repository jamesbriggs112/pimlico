/** @type { import('@storybook/server').Preview } */
const preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
      server: {
        url: 'https://drupal-skeleton.test/storybook/stories/render',
      },
    },
  },
};

export default preview;
