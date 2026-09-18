<?php

namespace App\Support;

final class DiscountInput
{
    /**
     * @return array{percent: float, amount: float}
     */
    public static function parse(?string $raw, float $subtotal): array
    {
        $text = strtoupper(trim((string) $raw));
        $text = str_replace(['$', ' '], '', $text);
        $text = str_replace(',', '.', $text);

        if ($text === '' || $text === '0' || $text === '0.00' || $text === '%0' || $text === '0%') {
            return ['percent' => 0.0, 'amount' => 0.0];
        }

        $isPercent = str_contains($text, '%');
        $number = (float) str_replace('%', '', $text);

        if (! is_finite($number) || $number < 0) {
            return ['percent' => 0.0, 'amount' => 0.0];
        }

        $subtotal = max(0, round($subtotal, 2));

        if ($isPercent) {
            $percent = round(min(100, $number), 2);
            $amount = round($subtotal * ($percent / 100), 2);

            return ['percent' => $percent, 'amount' => min($amount, $subtotal)];
        }

        $amount = round(min($number, $subtotal), 2);
        $percent = $subtotal > 0 ? round(($amount / $subtotal) * 100, 2) : 0.0;

        return ['percent' => $percent, 'amount' => $amount];
    }

    public static function display(float $percent, float $amount): string
    {
        if ($percent > 0 && abs($percent - round($percent)) < 0.001) {
            return '%'.(int) round($percent);
        }

        if ($amount > 0) {
            return number_format($amount, 2, '.', '');
        }

        return '';
    }
}
