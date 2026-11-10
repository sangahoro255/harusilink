<?php
namespace App\Core;

class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        static $user = null;

        if (!self::check()) {
            return null;
        }

        if ($user === null) {
            $stmt = db()->prepare(
                'SELECT id, name, email, role, status FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1'
            );
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }

        return $user;
    }

    public static function requireLogin(): array
    {
        $user = self::user();

        if ($user === null || $user['status'] !== 'active') {
            self::logout();
            redirect('/login');
        }

        return $user;
    }

    public static function requireRole(string $role): array
    {
        $user = self::requireLogin();

        if ($user['role'] !== $role) {
            http_response_code(403);
            exit(e(t('err.forbidden')));
        }

        return $user;
    }
}