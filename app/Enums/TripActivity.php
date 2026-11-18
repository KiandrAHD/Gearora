<?php

namespace App\Enums;

/**
 * Aktivitas perjalanan pada Trip Planner.
 *
 * Nilai disimpan sebagai string pada `trip_recommendation_rules.activity` agar
 * Admin dapat menambah aktivitas baru lewat data bila diperlukan tanpa migrasi
 * enum. Enum ini menjadi daftar kanonik untuk validasi & tampilan.
 */
enum TripActivity: string
{
    case Camping = 'camping';
    case Trekking = 'trekking';
    case Hiking = 'hiking';
    case Mountaineering = 'mountaineering';
    case Picnic = 'picnic';
    case Overlanding = 'overlanding';

    /** Sentinel: aturan berlaku untuk semua aktivitas. */
    public const ANY = 'semua';

    public function label(): string
    {
        return match ($this) {
            self::Camping => 'Camping',
            self::Trekking => 'Trekking',
            self::Hiking => 'Hiking',
            self::Mountaineering => 'Mountaineering',
            self::Picnic => 'Picnic',
            self::Overlanding => 'Overlanding',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Nilai yang sah untuk field `activity` (termasuk sentinel "semua").
     *
     * @return array<int, string>
     */
    public static function ruleValues(): array
    {
        return [...self::values(), self::ANY];
    }
}
