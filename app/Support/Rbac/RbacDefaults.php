<?php

namespace App\Support\Rbac;

use App\Enums\PermissionName;
use App\Enums\RoleName;

/**
 * Sumber tunggal matriks role → permission (mengikuti PRD §7).
 * Dipakai oleh seeder (idempotent) dan test agar tidak ada matriks ganda.
 */
final class RbacDefaults
{
    /**
     * @return array<int, string>
     */
    public static function staffRoleOrder(): array
    {
        return RoleName::staffNames();
    }

    /**
     * @return array<string, array{label:string, description:string, permissions:array<int, string>}>
     */
    public static function roles(): array
    {
        return [
            RoleName::Owner->value => [
                'label' => RoleName::Owner->label(),
                'description' => 'Kontrol tertinggi aplikasi.',
                'permissions' => PermissionName::values(),
            ],
            RoleName::Admin->value => [
                'label' => RoleName::Admin->label(),
                'description' => 'Operasional bisnis sesuai permission.',
                'permissions' => [
                    PermissionName::DashboardView->value,
                    PermissionName::UsersView->value,
                    PermissionName::UsersUpdate->value,
                    PermissionName::CustomersView->value,
                    PermissionName::CategoriesView->value,
                    PermissionName::CategoriesCreate->value,
                    PermissionName::CategoriesUpdate->value,
                    PermissionName::CategoriesDelete->value,
                    PermissionName::ProductsView->value,
                    PermissionName::ProductsCreate->value,
                    PermissionName::ProductsUpdate->value,
                    PermissionName::ProductsArchive->value,
                    PermissionName::ProductsDelete->value,
                    PermissionName::InventoryView->value,
                    PermissionName::InventoryManage->value,
                    PermissionName::InventoryAdjustStock->value,
                    PermissionName::InventoryManageAssets->value,
                    PermissionName::RentalsView->value,
                    PermissionName::RentalsCreate->value,
                    PermissionName::RentalsUpdate->value,
                    PermissionName::RentalsCancel->value,
                    PermissionName::PaymentsView->value,
                    PermissionName::PaymentsVerify->value,
                    PermissionName::PaymentsReject->value,
                ],
            ],
            RoleName::WarehouseStaff->value => [
                'label' => RoleName::WarehouseStaff->label(),
                'description' => 'Operasional inventaris dan unit rental.',
                'permissions' => [
                    PermissionName::DashboardView->value,
                    PermissionName::CategoriesView->value,
                    PermissionName::ProductsView->value,
                    PermissionName::InventoryView->value,
                    PermissionName::InventoryManage->value,
                    PermissionName::InventoryAdjustStock->value,
                    PermissionName::InventoryManageAssets->value,
                    PermissionName::RentalsView->value,
                    PermissionName::RentalsUpdate->value,
                ],
            ],
            RoleName::Customer->value => [
                'label' => RoleName::Customer->label(),
                'description' => 'Pemesanan dan akses data milik sendiri.',
                'permissions' => [],
            ],
        ];
    }

    /**
     * @return array<int, array{name:string, label:string, group:string}>
     */
    public static function permissions(): array
    {
        return array_map(fn (PermissionName $permission) => [
            'name' => $permission->value,
            'label' => $permission->label(),
            'group' => $permission->group(),
        ], PermissionName::cases());
    }
}
