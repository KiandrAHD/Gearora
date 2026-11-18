<?php

namespace App\Enums;

/** Percobaan pembayaran ke provider. Satu Payment dapat punya banyak attempt. */
enum PaymentAttemptStatus: string
{
    case Pending = 'pending';
    case Settlement = 'settlement';
    case CaptureAccepted = 'capture_accepted';
    case CaptureChallenged = 'capture_challenged';
    case Denied = 'denied';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Chargeback = 'chargeback';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Settlement => 'Berhasil (settlement)',
            self::CaptureAccepted => 'Berhasil (capture)',
            self::CaptureChallenged => 'Ditinjau (challenge)',
            self::Denied => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
            self::Failed => 'Gagal',
            self::Refunded => 'Direfund',
            self::PartiallyRefunded => 'Direfund sebagian',
            self::Chargeback => 'Sengketa (chargeback)',
        };
    }

    public function isSuccessful(): bool
    {
        return in_array($this, [self::Settlement, self::CaptureAccepted], true);
    }

    public function isTerminalNegative(): bool
    {
        return in_array($this, [
            self::Denied,
            self::Cancelled,
            self::Expired,
            self::Failed,
        ], true);
    }

    /** Attempt masih dapat dipakai/ditunggu (belum final). */
    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::CaptureChallenged], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
