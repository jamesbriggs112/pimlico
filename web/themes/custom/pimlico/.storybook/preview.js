/** @type { import('@storybook/server').Preview } */
const preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
      server: {
        url: 'http://localhost:8888/storybook/stories/render',
      },
    },
  },
};

export default preview;
