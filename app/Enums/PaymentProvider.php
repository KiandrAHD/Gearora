<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case Midtrans = 'midtrans';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Midtrans => 'Midtrans',
            self::Manual => 'Manual',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
