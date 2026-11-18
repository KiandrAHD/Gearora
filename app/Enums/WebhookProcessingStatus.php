<?php

namespace App\Enums;

enum WebhookProcessingStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Duplicate = 'duplicate';
    case Rejected = 'rejected';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Diterima',
            self::Processed => 'Diproses',
            self::Duplicate => 'Duplikat',
            self::Rejected => 'Ditolak',
            self::Failed => 'Gagal',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
