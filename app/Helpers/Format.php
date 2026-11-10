<?php
namespace App\Helpers;

class Format
{
    public static function date(string $ymd): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);

        if (!$date) {
            return $ymd;
        }

        return t('weekday.' . $date->format('N')) . ', '
            . $date->format('j') . ' '
            . t('month.' . $date->format('n')) . ' '
            . $date->format('Y');
    }

    public static function time(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '';
    }
}