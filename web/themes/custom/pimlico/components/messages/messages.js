/**
 * @file
 * Messages: the dismiss button hides its message.
 */
(function (Drupal, once) {
  Drupal.behaviors.messages = {
    attach(context = document) {
      once('messages', '.js-message-button-hide', context).forEach(
        (dismissButton) => {
          dismissButton.addEventListener('click', (e) => {
            e.preventDefault();
            const message = dismissButton.parentNode;

            if (message.classList.contains('messages-list__item')) {
              message.style.opacity = 0;
              message.classList.add('visually-hidden');
            }
          });
        }
      );
    },
  };
})(Drupal, once);
