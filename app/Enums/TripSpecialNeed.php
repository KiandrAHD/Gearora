<?php

namespace App\Enums;

/**
 * Kebutuhan khusus pada Trip Planner. Dapat dipilih lebih dari satu.
 */
enum TripSpecialNeed: string
{
    case RainProtection = 'rain_protection';
    case ColdWeather = 'cold_weather';
    case LightweightGear = 'lightweight_gear';
    case FamilyFriendly = 'family_friendly';
    case BeginnerFriendly = 'beginner_friendly';
    case CookingEquipment = 'cooking_equipment';
    case LargeGroup = 'large_group';
    case MinimalistSetup = 'minimalist_setup';

    public function label(): string
    {
        return match ($this) {
            self::RainProtection => 'Perlindungan hujan',
            self::ColdWeather => 'Cuaca dingin',
            self::LightweightGear => 'Gear ringan',
            self::FamilyFriendly => 'Ramah keluarga',
            self::BeginnerFriendly => 'Ramah pemula',
            self::CookingEquipment => 'Peralatan memasak',
            self::LargeGroup => 'Grup besar',
            self::MinimalistSetup => 'Setup minimalis',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
