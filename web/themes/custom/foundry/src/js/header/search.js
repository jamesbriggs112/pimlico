const Search = () => {
  document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.search-button');
    const searchRegion = document.querySelector('.region-search');

    if (!buttons.length) {
      return;
    }

    [].forEach.call(buttons, function (button) {
      button.addEventListener('click', () => {
        searchRegion.classList.toggle('open');
      });
    });
  });
};

export default Search;
