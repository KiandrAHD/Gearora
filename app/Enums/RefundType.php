<?php

namespace App\Enums;

enum RefundType: string
{
    case Rental = 'rental';
    case Deposit = 'deposit';
    case Combined = 'combined';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Rental => 'Biaya sewa',
            self::Deposit => 'Deposit',
            self::Combined => 'Sewa + deposit',
            self::Adjustment => 'Penyesuaian',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
