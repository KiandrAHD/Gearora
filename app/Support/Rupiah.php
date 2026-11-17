<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Format angka menjadi mata uang Rupiah dengan pemisah ribuan Indonesia.
 *
 * Contoh: 1250000 -> "Rp 1.250.000"
 */
final class Rupiah
{
    public static function format(int|float|string|null $amount, int $decimals = 0, bool $withPrefix = true): string
    {
        if ($decimals < 0) {
            throw new InvalidArgumentException('Jumlah desimal Rupiah tidak boleh negatif.');
        }

        $value = self::normalize($amount);
        $sign = $value < 0 ? '-' : '';
        $formatted = number_format(abs($value), $decimals, ',', '.');

        return $withPrefix ? "{$sign}Rp {$formatted}" : "{$sign}{$formatted}";
    }

    public static function value(int|float|string|null $amount): float
    {
        return self::normalize($amount);
    }

    private static function normalize(int|float|string|null $amount): float
    {
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        if (is_string($amount)) {
            $amount = str_replace([' ', "\u{00A0}"], '', trim($amount));

            if (str_contains($amount, ',')) {
                $amount = str_replace(['.', ','], ['', '.'], $amount);
            } elseif (substr_count($amount, '.') > 1) {
                $amount = str_replace('.', '', $amount);
            } elseif (preg_match('/^-?\d+\.\d{3}$/', $amount) === 1) {
                $amount = str_replace('.', '', $amount);
            }
        }

        return (float) $amount;
    }
}
