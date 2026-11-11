/**
 * @file
 * Map building picker: a click on the map fills a Unit's building input.
 *
 * Fills the input the editor last focused, else the first empty one, else
 * the last one.
 */
((Drupal, once) => {
  Drupal.behaviors.pimlicoMapPicker = {
    attach(context) {
      once(
        'pimlico-map-picker',
        'pimlico-map[data-picker-for]',
        context
      ).forEach((map) => {
        const wrapper = document.getElementById(map.dataset.pickerFor);
        if (!wrapper) {
          return;
        }
        let current = null;
        const choose = (event) => {
          if (event.target.matches('[data-picker-input]')) {
            current = event.target;
          }
        };
        wrapper.addEventListener('focusin', choose);
        wrapper.addEventListener('click', choose);
        map.style.height = '420px';
        map.addEventListener('pimlico-map:select', (event) => {
          const id = event.detail && event.detail.buildingId;
          if (!id) {
            return;
          }
          const inputs = [...wrapper.querySelectorAll('[data-picker-input]')];
          const input =
            (current && wrapper.contains(current) && current) ||
            inputs.find((field) => !field.value) ||
            inputs[inputs.length - 1];
          if (!input) {
            return;
          }
          input.value = id;
          input.dispatchEvent(new Event('change', { bubbles: true }));
          Drupal.announce(Drupal.t('Map building set to @id.', { '@id': id }));
        });
      });
    },
  };
})(Drupal, once);
