<?php

namespace App\Support;

class WhatsAppNumber
{
    public static function digits(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            return '52'.$digits;
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return '52'.substr($digits, 3);
        }

        if (str_starts_with($digits, '52') && strlen($digits) >= 12) {
            return $digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }
}
