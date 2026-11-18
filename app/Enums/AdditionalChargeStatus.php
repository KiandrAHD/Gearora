<?php

namespace App\Enums;

enum AdditionalChargeStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';
    case OffsetFromDeposit = 'offset_from_deposit';
    case PartiallySettled = 'partially_settled';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Menunggu persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Paid => 'Dibayar',
            self::OffsetFromDeposit => 'Dipotong dari deposit',
            self::PartiallySettled => 'Selesai sebagian',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::OffsetFromDeposit], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
