<?php
namespace App\Helpers;

class Url
{
    public static function absolute(string $path): string
    {
        $base = trim((string) env('APP_URL', ''));

        if ($base !== '' && preg_match('#^https?://[A-Za-z0-9.\-:\[\]]+(/[A-Za-z0-9_.\-/]*)?$#', $base)) {
            return rtrim($base, '/') . $path;
        }

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {
            $host = 'localhost';
        }

        return ($https ? 'https' : 'http') . '://' . $host . url($path);
    }
}