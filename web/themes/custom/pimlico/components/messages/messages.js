(function (Drupal) {
  Drupal.behaviors.messages = {
    attach(context = document) {
      once('four-messages-dismiss', '.js-message-button-hide', context).forEach(
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
