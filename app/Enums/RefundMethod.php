<?php

namespace App\Enums;

enum RefundMethod: string
{
    case Gateway = 'gateway';
    case ManualTransfer = 'manual_transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Gateway => 'Otomatis (gateway)',
            self::ManualTransfer => 'Transfer manual',
            self::Cash => 'Tunai',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
