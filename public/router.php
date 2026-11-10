<?php
// Inatumika na serve.bat / serve.sh pekee (php -S). Haina kazi chini ya Apache.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath(__DIR__ . $path);

// Faili halisi (CSS, JS, picha) zinatolewa kama zilivyo.
if ($file !== false && is_file($file) && strpos($file, realpath(__DIR__)) === 0 && substr($file, -4) !== '.php') {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF']    = '/index.php';

require __DIR__ . '/index.php';
