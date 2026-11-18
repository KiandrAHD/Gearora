<?php

namespace App\Support;

/**
 * Utilitas uang: semua nilai disimpan/dihitung sebagai integer Rupiah penuh
 * untuk menghindari galat floating point (ADR-5.6).
 */
final class Money
{
    public static function toInt(int|float|string|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round((float) $value);
    }

    public static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    /** Bulatkan ke atas ke kelipatan tertentu. */
    public static function ceilTo(int $value, int $multiple): int
    {
        if ($multiple <= 1) {
            return $value;
        }

        return (int) (ceil($value / $multiple) * $multiple);
    }
}
