<?php
namespace App\Services;

class RateLimiter
{
    public static function tooMany(string $key, int $max, int $seconds): bool
    {
        $seconds = max(1, $seconds);

        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM rate_limits
             WHERE rkey = :key AND created_at > (NOW() - INTERVAL ' . $seconds . ' SECOND)'
        );
        $stmt->execute([':key' => $key]);

        return (int) $stmt->fetchColumn() >= $max;
    }

    public static function hit(string $key): void
    {
        $pdo = db();

        $pdo->prepare('INSERT INTO rate_limits (rkey) VALUES (:key)')->execute([':key' => $key]);

        if (random_int(1, 50) === 1) {
            $pdo->exec('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
        }
    }

    public static function clear(string $key): void
    {
        db()->prepare('DELETE FROM rate_limits WHERE rkey = :key')->execute([':key' => $key]);
    }

    public static function clientKey(string $prefix): string
    {
        return $prefix . ':' . substr(hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 32);
    }
}