<?php
namespace App\Controllers;

class LanguageController
{
    public function change(): void
    {
        csrf_verify();

        $lang = post('lang');
        if (in_array($lang, ['sw', 'en'], true)) {
            setcookie('lang', $lang, [
                'expires'  => time() + 31536000,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']),
            ]);
        }

        $next = post('next');
        if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '\\')) {
            $next = '/';
        }

        redirect($next);
    }
}