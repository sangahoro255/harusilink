<?php
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);
        $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";

        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $pdo->exec("SET time_zone = '+03:00'");
    }

    return $pdo;
}