<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Midtrans = 'midtrans';
    case Transfer = 'transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Midtrans => 'Pembayaran otomatis (Midtrans)',
            self::Transfer => 'Transfer bank',
            self::Cash => 'Tunai di lokasi',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Midtrans => 'Midtrans',
            self::Transfer => 'Transfer',
            self::Cash => 'Tunai',
        };
    }

    /** Pembayaran otomatis via gateway (Webhook-driven). */
    public function isGateway(): bool
    {
        return $this === self::Midtrans;
    }

    /** Pembayaran yang membutuhkan verifikasi manual Admin. */
    public function isManual(): bool
    {
        return in_array($this, [self::Transfer, self::Cash], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<int, string> */
    public static function manualValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->isManual()),
        ));
    }
}
