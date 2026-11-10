<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

function env(string $key, $default = null)
{
    static $vars = null;
    if ($vars === null) {
        $vars = parse_ini_file(BASE_PATH . '/.env', false, INI_SCANNER_RAW) ?: [];
    }
    return $vars[$key] ?? $default;
}

date_default_timezone_set('Africa/Dar_es_Salaam');

error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG') === 'true' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/app.log');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . $path;
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function request_path(): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    return '/' . trim(substr($uri, strlen($base)), '/');
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require BASE_PATH . '/views/' . $name . '.php';
}

function current_lang(): string
{
    static $lang = null;
    if ($lang === null) {
        $cookie = $_COOKIE['lang'] ?? '';
        $lang = in_array($cookie, ['sw', 'en'], true) ? $cookie : 'sw';
    }
    return $lang;
}

function t(string $key, array $params = []): string
{
    static $dictionaries = [];
    $lang = current_lang();

    if (!isset($dictionaries[$lang])) {
        $dictionary = require BASE_PATH . '/lang/' . $lang . '.php';
        foreach (glob(BASE_PATH . '/lang/' . $lang . '/*.php') ?: [] as $file) {
            $dictionary = array_merge($dictionary, require $file);
        }
        $dictionaries[$lang] = $dictionary;
    }

    $text = $dictionaries[$lang][$key] ?? $key;

    foreach ($params as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}

set_exception_handler(function (\Throwable $ex): void {
    error_log(get_class($ex) . ': ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());

    if (!headers_sent()) {
        http_response_code(500);
    }

    $debug = env('APP_DEBUG') === 'true'
        ? get_class($ex) . ': ' . $ex->getMessage() . "\n" . $ex->getFile() . ':' . $ex->getLine()
        : null;

    try {
        view('errors/500', ['debugMessage' => $debug]);
    } catch (\Throwable $inner) {
        echo 'Something went wrong.';
    }
});

\App\Helpers\SecureHeaders::applyDefaults();

$secureConnection = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secureConnection,
]);
session_start();

if (isset($_SESSION['user_id'])) {
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

    if ($lastActivity > 0 && time() - $lastActivity > 7200) {
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['last_activity']);
    } else {
        $_SESSION['last_activity'] = time();
    }
}

require BASE_PATH . '/config/database.php';

function post(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? $v : '';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = post('_csrf');
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit(e(t('err.csrf')));
    }
}

function redirect(string $path)
{
    header('Location: ' . url($path));
    exit;
}