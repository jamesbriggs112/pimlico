/**
 * @file
 * Street map filter: a category button sets the map's category attribute,
 * so only that category's tenancies stay yellow (and the search follows).
 */
((Drupal, once) => {
  Drupal.behaviors.pimlicoStreetMapFilter = {
    attach(context) {
      once('street-map-filter', '[data-map-filter]', context).forEach((bar) => {
        const map = document.getElementById(bar.dataset.mapFilter);
        if (!map) {
          return;
        }
        bar.addEventListener('click', (event) => {
          const button = event.target.closest('button[data-category]');
          if (!button) {
            return;
          }
          const { category } = button.dataset;
          if (category) {
            map.setAttribute('category', category);
          } else {
            map.removeAttribute('category');
          }
          bar.querySelectorAll('button[data-category]').forEach((item) => {
            item.setAttribute('aria-pressed', String(item === button));
          });
        });
      });
    },
  };
})(Drupal, once);
