<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case WaitingVerification = 'waiting_verification';
    case Processing = 'processing';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum dibayar',
            self::WaitingVerification => 'Menunggu verifikasi',
            self::Processing => 'Sedang diproses',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
            self::Expired => 'Kedaluwarsa',
            self::Failed => 'Gagal',
            self::Refunded => 'Dana dikembalikan',
            self::PartiallyRefunded => 'Dana dikembalikan sebagian',
        };
    }

    /** Status yang dianggap sudah menerima dana (revenue/deposit dapat dialokasikan). */
    public function isSettled(): bool
    {
        return in_array($this, [
            self::Verified,
            self::Refunded,
            self::PartiallyRefunded,
        ], true);
    }

    /** Status final; tidak boleh diturunkan oleh notifikasi yang datang telat. */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Verified,
            self::Refunded,
            self::PartiallyRefunded,
            self::Expired,
            self::Failed,
        ], true);
    }

    public function isRejected(): bool
    {
        return in_array($this, [self::Rejected, self::Expired, self::Failed], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
