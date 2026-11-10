document.addEventListener('click', function (event) {
  var button = event.target.closest('[data-copy-path]');

  if (!button) {
    return;
  }

  var link = new URL(button.getAttribute('data-copy-path'), window.location.origin).href;

  var done = function () {
    var original = button.textContent;
    button.textContent = button.getAttribute('data-copied') || 'OK';
    setTimeout(function () {
      button.textContent = original;
    }, 1500);
  };

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(link).then(done, function () {
      window.prompt('', link);
    });
  } else {
    window.prompt('', link);
  }
});