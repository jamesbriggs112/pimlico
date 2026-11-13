/** @type { import('@storybook/server').Preview } */
const preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
      server: {
        url: 'https://pimlico.test/storybook/stories/render',
      },
    },
  },
};

export default preview;
