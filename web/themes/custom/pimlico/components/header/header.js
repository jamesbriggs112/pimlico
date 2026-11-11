(function (Drupal) {
  Drupal.behaviors.header = {
    attach(context) {
      once(
        'header',
        '[data-component-id="pimlico:header"]',
        context
      ).forEach((rootNode) => {
        const menuButton = rootNode.querySelector('.hamburger');
        const pageBody = document.getElementsByTagName('body')[0];

        if (!menuButton) {
          return;
        }

        menuButton.addEventListener('click', () => {
          menuButton.classList.toggle('is-active');
          pageBody.classList.toggle('mobile-nav-open');
        });
      });
    },
  };
})(Drupal);
