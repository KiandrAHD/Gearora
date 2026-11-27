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
            self::Midtrans => 'QRIS / e-wallet & transfer otomatis (Midtrans)',
            self::Transfer => 'Transfer bank manual',
            self::Cash => 'Bayar tunai saat pengambilan',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Midtrans => 'QRIS / Midtrans',
            self::Transfer => 'Transfer',
            self::Cash => 'Tunai saat ambil',
        };
    }

    /** Penjelasan singkat untuk UI pemilihan metode. */
    public function description(): string
    {
        return match ($this) {
            self::Midtrans => 'Bayar dengan QRIS, e-wallet, kartu, atau VA bank. Status terverifikasi otomatis melalui Midtrans.',
            self::Transfer => 'Transfer ke rekening GEARORA, lalu unggah bukti. Diverifikasi manual oleh admin.',
            self::Cash => 'Bayar tunai langsung di basecamp/toko saat gear diambil. Bukan COD pengiriman.',
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
