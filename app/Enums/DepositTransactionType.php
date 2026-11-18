<?php

namespace App\Enums;

enum DepositTransactionType: string
{
    case Collected = 'collected';
    case Deduction = 'deduction';
    case Refund = 'refund';
    case Forfeiture = 'forfeiture';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Collected => 'Deposit diterima',
            self::Deduction => 'Pemotongan deposit',
            self::Refund => 'Pengembalian deposit',
            self::Forfeiture => 'Deposit hangus',
            self::Adjustment => 'Penyesuaian',
        };
    }

    /**
     * Arah saldo: +1 menambah saldo yang ditahan, -1 mengurangi saldo.
     */
    public function sign(): int
    {
        return match ($this) {
            self::Collected, self::Adjustment => 1,
            self::Deduction, self::Refund, self::Forfeiture => -1,
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
