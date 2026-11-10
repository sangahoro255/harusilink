<?php
namespace App\Helpers;

class FormNonce
{
    public static function issue(string $scope): string
    {
        $nonce = bin2hex(random_bytes(16));
        $_SESSION['nonces'][$scope] = $nonce;

        return $nonce;
    }

    public static function consume(string $scope, string $value): bool
    {
        $expected = $_SESSION['nonces'][$scope] ?? '';
        unset($_SESSION['nonces'][$scope]);

        return is_string($expected) && $expected !== '' && hash_equals($expected, $value);
    }
}