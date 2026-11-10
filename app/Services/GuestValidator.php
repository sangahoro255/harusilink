<?php
namespace App\Services;

use App\Models\Guest;

class GuestValidator
{
    public static function check(array $in): array
    {
        $errors = [];
        $data = ['full_name' => $in['full_name'], 'phone' => null, 'email' => null, 'seats' => 1];

        if (mb_strlen($in['full_name']) < 2 || mb_strlen($in['full_name']) > 120) {
            $errors[] = t('err.guest_name');
        }

        if ($in['phone'] !== '') {
            $phone = Guest::normalizePhone($in['phone']);
            if ($phone === null) {
                $errors[] = t('err.guest_phone');
            }
            $data['phone'] = $phone;
        }

        if ($in['email'] !== '') {
            if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || strlen($in['email']) > 190) {
                $errors[] = t('err.guest_email');
            } else {
                $data['email'] = $in['email'];
            }
        }

        if (!ctype_digit($in['seats']) || (int) $in['seats'] < 1 || (int) $in['seats'] > 20) {
            $errors[] = t('err.guest_seats');
        } else {
            $data['seats'] = (int) $in['seats'];
        }

        return [$errors, $data];
    }
}