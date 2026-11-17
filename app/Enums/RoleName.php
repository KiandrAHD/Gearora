<?php

namespace App\Enums;

enum RoleName: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case WarehouseStaff = 'warehouse_staff';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::WarehouseStaff => 'Petugas Gudang',
            self::Customer => 'Pelanggan',
        };
    }

    /** @return array<int, string> */
    public static function staffNames(): array
    {
        return [self::Owner->value, self::Admin->value, self::WarehouseStaff->value];
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
