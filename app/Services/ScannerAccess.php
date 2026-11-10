<?php
namespace App\Services;

class ScannerAccess
{
    private const IP_LIMIT       = 8;
    private const WEDDING_LIMIT  = 40;
    private const WINDOW_MINUTES = 15;
    private const SESSION_HOURS  = 12;

    public static function findBySlug(string $slug): ?array
    {
        if (!preg_match('/^[a-z0-9][a-z0-9\-]{2,80}$/', $slug)) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT id, slug, status, bride_name, groom_name, scanner_pin_hash
             FROM weddings
             WHERE slug = :slug AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':slug' => $slug]);

        return $stmt->fetch() ?: null;
    }

    public static function attempt(string $slug, string $pin): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $wedding = self::findBySlug($slug);
        $weddingId = $wedding !== null ? (int) $wedding['id'] : null;

        if (self::blocked($ip, $weddingId)) {
            return 'blocked';
        }

        $hash = $wedding['scanner_pin_hash'] ?? null;
        $valid = false;

        if ($wedding !== null && is_string($hash) && $hash !== '' && preg_match('/^\d{6}$/', $pin) === 1) {
            $valid = password_verify($pin, $hash);
        } else {
            password_hash($pin, PASSWORD_DEFAULT);
        }

        if ($valid) {
            self::start($wedding);
            return 'ok';
        }

        self::recordFailure($ip, $weddingId);
        return 'bad';
    }

    public static function current(): ?array
    {
        $session = $_SESSION['scanner'] ?? null;

        if (!is_array($session) || (int) ($session['until'] ?? 0) < time()) {
            unset($_SESSION['scanner']);
            return null;
        }

        $stmt = db()->prepare(
            'SELECT id, slug, status, bride_name, groom_name, scanner_pin_hash
             FROM weddings
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => (int) ($session['wedding_id'] ?? 0)]);
        $wedding = $stmt->fetch();

        $hash = $wedding['scanner_pin_hash'] ?? null;

        if (!$wedding || !is_string($hash) || $hash === ''
            || !hash_equals((string) ($session['stamp'] ?? ''), hash('sha256', $hash))) {
            unset($_SESSION['scanner']);
            return null;
        }

        unset($wedding['scanner_pin_hash']);

        return $wedding;
    }

    public static function logout(): void
    {
        unset($_SESSION['scanner']);
    }

    public static function setPin(int $weddingId, int $ownerId): ?string
    {
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = db()->prepare(
            'UPDATE weddings SET scanner_pin_hash = :hash
             WHERE id = :id AND owner_id = :owner AND deleted_at IS NULL'
        );
        $stmt->execute([':hash' => password_hash($pin, PASSWORD_DEFAULT), ':id' => $weddingId, ':owner' => $ownerId]);

        return $stmt->rowCount() > 0 ? $pin : null;
    }

    public static function clearPin(int $weddingId, int $ownerId): bool
    {
        $stmt = db()->prepare(
            'UPDATE weddings SET scanner_pin_hash = NULL
             WHERE id = :id AND owner_id = :owner AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $weddingId, ':owner' => $ownerId]);

        return $stmt->rowCount() > 0;
    }

    public static function hasPin(int $weddingId, int $ownerId): bool
    {
        $stmt = db()->prepare(
            'SELECT scanner_pin_hash IS NOT NULL FROM weddings
             WHERE id = :id AND owner_id = :owner AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $weddingId, ':owner' => $ownerId]);

        return (bool) $stmt->fetchColumn();
    }

    private static function start(array $wedding): void
    {
        session_regenerate_id(true);

        $_SESSION['scanner'] = [
            'wedding_id' => (int) $wedding['id'],
            'stamp'      => hash('sha256', (string) $wedding['scanner_pin_hash']),
            'until'      => time() + self::SESSION_HOURS * 3600,
        ];
    }

    private static function blocked(string $ip, ?int $weddingId): bool
    {
        $pdo = db();
        $window = '(NOW() - INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE)';

        $byIp = $pdo->prepare("SELECT COUNT(*) FROM scanner_attempts WHERE ip = :ip AND created_at > $window");
        $byIp->execute([':ip' => $ip]);

        if ((int) $byIp->fetchColumn() >= self::IP_LIMIT) {
            return true;
        }

        if ($weddingId !== null) {
            $byWedding = $pdo->prepare(
                "SELECT COUNT(*) FROM scanner_attempts WHERE wedding_id = :wedding AND created_at > $window"
            );
            $byWedding->execute([':wedding' => $weddingId]);

            if ((int) $byWedding->fetchColumn() >= self::WEDDING_LIMIT) {
                return true;
            }
        }

        return false;
    }

    private static function recordFailure(string $ip, ?int $weddingId): void
    {
        $pdo = db();

        $pdo->prepare('INSERT INTO scanner_attempts (ip, wedding_id) VALUES (:ip, :wedding)')
            ->execute([':ip' => $ip, ':wedding' => $weddingId]);

        if (random_int(1, 20) === 1) {
            $pdo->exec('DELETE FROM scanner_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
        }
    }
}