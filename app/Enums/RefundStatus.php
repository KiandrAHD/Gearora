<?php

namespace App\Enums;

enum RefundStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case ManualRequired = 'manual_required';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Menunggu persetujuan',
            self::Approved => 'Disetujui',
            self::Processing => 'Sedang diproses',
            self::Succeeded => 'Berhasil',
            self::Failed => 'Gagal',
            self::ManualRequired => 'Perlu refund manual',
            self::Rejected => 'Ditolak',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::PendingApproval, self::Approved, self::Processing, self::ManualRequired], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
