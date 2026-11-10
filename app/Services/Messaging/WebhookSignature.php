<?php
namespace App\Services\Messaging;

class WebhookSignature
{
    public static function valid(string $body, string $header, string $secret): bool
    {
        if ($secret === '' || !str_starts_with($header, 'sha256=')) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $secret), substr($header, 7));
    }
}