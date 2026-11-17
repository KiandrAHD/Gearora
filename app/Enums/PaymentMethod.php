<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Transfer = 'transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Transfer bank',
            self::Cash => 'Tunai di lokasi',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
