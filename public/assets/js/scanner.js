(function () {
  var root = document.getElementById('cam');

  if (!root) {
    return;
  }

  var video = document.getElementById('cam-video');
  var startButton = document.getElementById('cam-start');
  var stopButton = document.getElementById('cam-stop');
  var statusLine = document.getElementById('cam-status');
  var verifyBase = root.getAttribute('data-verify-base');
  var canvas = document.createElement('canvas');
  var context = canvas.getContext('2d', { willReadFrequently: true });
  var stream = null;
  var timer = null;
  var done = false;
  var busy = false;
  var detector = null;

  // Kisomaji cha QR kilichojengwa ndani ya kivinjari (Chrome/Edge/Android) - hakihitaji maktaba.
  // Ikiwa hakipo, jsQR.js (ikiwekwa kwenye public/assets/js/) inatumika.
  if ('BarcodeDetector' in window) {
    try {
      detector = new window.BarcodeDetector({ formats: ['qr_code'] });
    } catch (error) {
      detector = null;
    }
  }

  function say(key) {
    statusLine.textContent = root.getAttribute('data-msg-' + key) || '';
  }

  function remember(on) {
    try {
      if (on) {
        sessionStorage.setItem('hl_cam', '1');
      } else {
        sessionStorage.removeItem('hl_cam');
      }
    } catch (error) {
    }
  }

  function wasOn() {
    try {
      return sessionStorage.getItem('hl_cam') === '1';
    } catch (error) {
      return false;
    }
  }

  function stop() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }

    if (stream) {
      stream.getTracks().forEach(function (track) {
        track.stop();
      });
      stream = null;
    }

    video.srcObject = null;
    root.classList.remove('on');
  }

  function tokenFrom(text) {
    var value = String(text).toLowerCase();
    var match = value.match(/\/verify\/([a-f0-9]{32})(?:[\/?#]|$)/) || value.match(/^([a-f0-9]{32})$/);

    return match ? match[1] : null;
  }

  function handle(text) {
    var token = tokenFrom(text);

    if (token === null) {
      say('notours');
      return;
    }

    done = true;

    if (navigator.vibrate) {
      navigator.vibrate(120);
    }

    say('found');
    stop();
    window.location.href = verifyBase + token;
  }

  function tick() {
    if (done || busy || video.readyState < 2 || !video.videoWidth || !video.videoHeight) {
      return;
    }

    if (detector) {
      busy = true;
      detector
        .detect(video)
        .then(function (codes) {
          busy = false;
          if (codes && codes.length && codes[0].rawValue) {
            handle(codes[0].rawValue);
          }
        })
        .catch(function () {
          busy = false;
        });
      return;
    }

    var scale = Math.min(1, 640 / Math.max(video.videoWidth, video.videoHeight));
    canvas.width = Math.round(video.videoWidth * scale);
    canvas.height = Math.round(video.videoHeight * scale);
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    var image = context.getImageData(0, 0, canvas.width, canvas.height);
    var code = window.jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' });

    if (!code || !code.data) {
      return;
    }

    handle(code.data);
  }

  function start() {
    if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      say('insecure');
      return;
    }

    if (!detector && typeof window.jsQR !== 'function') {
      say('nolib');
      return;
    }

    done = false;
    busy = false;

    navigator.mediaDevices
      .getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
      .then(function (media) {
        stream = media;
        video.srcObject = media;
        return video.play();
      })
      .then(function () {
        remember(true);
        root.classList.add('on');
        say('scanning');
        timer = setInterval(tick, 200);
      })
      .catch(function () {
        stop();
        remember(false);
        say('denied');
      });
  }

  startButton.addEventListener('click', start);

  stopButton.addEventListener('click', function () {
    remember(false);
    stop();
    say('idle');
  });

  window.addEventListener('pagehide', stop);

  if (wasOn()) {
    start();
  }
})();