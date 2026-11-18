<?php

namespace App\Enums;

/**
 * State machine Sales Order (retail). Terpisah dari PaymentStatus.
 *
 * pending_payment -> payment_review -> confirmed -> ready_for_pickup -> completed
 * cabang: pending_payment -> cancelled|expired, payment_review -> confirmed|cancelled,
 *         confirmed|ready_for_pickup -> cancelled (selama belum completed).
 */
enum SalesOrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentReview = 'payment_review';
    case Confirmed = 'confirmed';
    case ReadyForPickup = 'ready_for_pickup';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu pembayaran',
            self::PaymentReview => 'Menunggu verifikasi pembayaran',
            self::Confirmed => 'Terkonfirmasi',
            self::ReadyForPickup => 'Siap diambil',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Expired], true);
    }

    /** Status yang masih menahan reservasi stok. */
    public function blocksStock(): bool
    {
        return in_array($this, [
            self::PendingPayment,
            self::PaymentReview,
            self::Confirmed,
            self::ReadyForPickup,
        ], true);
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PaymentReview, self::Cancelled, self::Expired],
            self::PaymentReview => [self::Confirmed, self::PendingPayment, self::Cancelled],
            self::Confirmed => [self::ReadyForPickup, self::Cancelled],
            self::ReadyForPickup => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled, self::Expired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
