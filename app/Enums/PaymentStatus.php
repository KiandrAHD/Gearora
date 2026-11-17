<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case WaitingVerification = 'waiting_verification';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum dibayar',
            self::WaitingVerification => 'Menunggu verifikasi',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
            self::Refunded => 'Dana dikembalikan',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
