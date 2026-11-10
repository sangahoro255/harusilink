<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Services\AuditLog;
use App\Services\RateLimiter;

class AuthController
{
    public function showRegister(): void
    {
        view('auth/register', ['errors' => [], 'old' => []]);
    }

    public function register(): void
    {
        csrf_verify();

        $name     = trim(post('name'));
        $email    = strtolower(trim(post('email')));
        $phone    = trim(post('phone'));
        $password = post('password');
        $confirm  = post('password_confirm');
        $errors   = [];
        $key      = RateLimiter::clientKey('register');

        if (RateLimiter::tooMany($key, 10, 3600)) {
            http_response_code(429);
            view('auth/register', [
                'errors' => [t('err.too_many')],
                'old'    => ['name' => $name, 'email' => $email, 'phone' => $phone],
            ]);
            return;
        }

        RateLimiter::hit($key);

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors[] = t('err.name');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = t('err.email');
        }
        if ($phone !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $phone)) {
            $errors[] = t('err.phone');
        }
        if (strlen($password) < 8) {
            $errors[] = t('err.password_short');
        }
        if ($password !== $confirm) {
            $errors[] = t('err.password_mismatch');
        }

        if (!$errors) {
            try {
                $stmt = db()->prepare(
                    "INSERT INTO users (name, email, phone, password_hash, role)
                     VALUES (:name, :email, :phone, :hash, 'owner')"
                );
                $stmt->execute([
                    ':name'  => $name,
                    ':email' => $email,
                    ':phone' => $phone !== '' ? $phone : null,
                    ':hash'  => password_hash($password, PASSWORD_DEFAULT),
                ]);
                redirect('/login?registered=1');
            } catch (\PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    $errors[] = t('err.email_taken');
                } else {
                    error_log($ex->getMessage());
                    $errors[] = t('err.generic');
                }
            }
        }

        view('auth/register', [
            'errors' => $errors,
            'old'    => ['name' => $name, 'email' => $email, 'phone' => $phone],
        ]);
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }

        view('auth/login', [
            'error'      => null,
            'old'        => '',
            'registered' => isset($_GET['registered']),
        ]);
    }

    public function login(): void
    {
        csrf_verify();

        $email    = strtolower(trim(post('email')));
        $password = post('password');
        $ipKey    = RateLimiter::clientKey('login-ip');
        $acctKey  = 'login-acct:' . substr(hash('sha256', $email), 0, 32);

        if (RateLimiter::tooMany($ipKey, 15, 900) || RateLimiter::tooMany($acctKey, 5, 900)) {
            http_response_code(429);
            view('auth/login', ['error' => t('err.too_many_login'), 'old' => $email, 'registered' => false]);
            return;
        }

        $stmt = db()->prepare(
            'SELECT id, role, status, password_hash FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $valid = password_verify($password, $user['password_hash']);
        } else {
            password_hash($password, PASSWORD_DEFAULT);
            $valid = false;
        }

        if ($valid && $user['status'] === 'active') {
            RateLimiter::clear($acctKey);

            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
                    ->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['id']]);
            }

            Auth::login($user);
            db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')
                ->execute([':id' => $user['id']]);
            redirect('/dashboard');
        }

        if (!$valid) {
            RateLimiter::hit($ipKey);
            RateLimiter::hit($acctKey);
            AuditLog::record(null, 'auth.login_failed');
        }

        $error = $valid ? t('err.suspended') : t('err.invalid_credentials');

        view('auth/login', ['error' => $error, 'old' => $email, 'registered' => false]);
    }

    public function logout(): void
    {
        csrf_verify();
        Auth::logout();
        redirect('/login');
    }
}