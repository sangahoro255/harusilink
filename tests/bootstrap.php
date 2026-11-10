<?php
define('BASE_PATH', dirname(__DIR__));

$GLOBALS['TEST_ENV'] = [];

function env(string $key, $default = null)
{
    return $GLOBALS['TEST_ENV'][$key] ?? $default;
}

function t(string $key, array $params = []): string
{
    return $key;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . $path;
}

function request_path(): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    return '/' . trim(substr($uri, strlen($base)), '/');
}

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