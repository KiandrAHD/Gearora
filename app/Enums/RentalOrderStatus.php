<?php

namespace App\Enums;

enum RentalOrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentReview = 'payment_review';
    case Confirmed = 'confirmed';
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';
    case Returned = 'returned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu pembayaran',
            self::PaymentReview => 'Menunggu verifikasi pembayaran',
            self::Confirmed => 'Terkonfirmasi',
            self::ReadyForPickup => 'Siap diambil',
            self::PickedUp => 'Sedang disewa',
            self::Returned => 'Dikembalikan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /** Status yang masih menahan unit fisik pada periode rental-nya. */
    public function blocksAvailability(): bool
    {
        return in_array($this, self::blockingStatuses(), true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    /** @return array<int, self> */
    public static function blockingStatuses(): array
    {
        return [
            self::PendingPayment,
            self::PaymentReview,
            self::Confirmed,
            self::ReadyForPickup,
            self::PickedUp,
        ];
    }

    /**
     * Transisi status yang sah pada Phase 4 (pembuatan order + verifikasi pembayaran).
     * Transisi pickup/return penuh diimplementasikan pada Phase 5.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PaymentReview, self::Cancelled, self::Confirmed],
            self::PaymentReview => [self::Confirmed, self::PendingPayment, self::Cancelled],
            self::Confirmed => [self::ReadyForPickup, self::Cancelled],
            self::ReadyForPickup => [self::PickedUp, self::Cancelled],
            self::PickedUp => [self::Returned],
            self::Returned => [self::Completed],
            self::Completed, self::Cancelled => [],
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
