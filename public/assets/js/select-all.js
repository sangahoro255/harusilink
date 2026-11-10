document.addEventListener('change', function (event) {
  var master = event.target.closest('[data-select-all]');

  if (!master) {
    return;
  }

  document.querySelectorAll(master.getAttribute('data-select-all')).forEach(function (box) {
    if (!box.disabled) {
      box.checked = master.checked;
    }
  });
});