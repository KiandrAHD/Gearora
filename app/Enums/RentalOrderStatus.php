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
     * Transisi status yang sah secara bisnis.
     *
     * Catatan Phase 5:
     * - `pending_payment → confirmed` dihapus: konfirmasi wajib lewat verifikasi
     *   pembayaran atau webhook gateway tervalidasi.
     * - `picked_up`, `returned`, dan `completed` hanya boleh dicapai melalui
     *   workflow service (handover/return/inspection), bukan dropdown generik.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PaymentReview, self::Cancelled],
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

    /**
     * Transisi yang hanya dapat dilakukan via workflow service.
     *
     * @return array<int, self>
     */
    public static function workflowOnlyTargets(): array
    {
        return [self::PickedUp, self::Returned, self::Completed];
    }

    public function isWorkflowOnlyTarget(): bool
    {
        return in_array($this, self::workflowOnlyTargets(), true);
    }

    /**
     * Transisi yang aman ditampilkan pada dropdown admin generik.
     *
     * @return array<int, self>
     */
    public function manualTransitions(): array
    {
        return array_values(array_filter(
            $this->allowedTransitions(),
            fn (self $target) => ! $target->isWorkflowOnlyTarget(),
        ));
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
