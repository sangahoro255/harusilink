<?php
namespace App\Helpers;

class Money
{
    public static function parse(string $raw): ?string
    {
        $value = preg_replace('/[\s,]/', '', trim($raw));

        if (!is_string($value) || !preg_match('/^\d{1,12}(\.\d{1,2})?$/', $value)) {
            return null;
        }

        return (float) $value > 0 ? $value : null;
    }

    public static function currency(): string
    {
        $currency = strtoupper(trim((string) env('CURRENCY', 'TZS')));

        return preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'TZS';
    }

    public static function format($amount): string
    {
        $number = (float) $amount;
        $decimals = abs($number - round($number)) < 0.005 ? 0 : 2;

        return self::currency() . ' ' . number_format($number, $decimals);
    }
}