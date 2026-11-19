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
                    PermissionName::PaymentsRefund->value,
                    PermissionName::PaymentsSettingsManage->value,
                    PermissionName::RentalsHandover->value,
                    PermissionName::RentalsReturn->value,
                    PermissionName::RentalsComplete->value,
                    PermissionName::InspectionsView->value,
                    PermissionName::InspectionsCreate->value,
                    PermissionName::InspectionsApprove->value,
                    PermissionName::DepositsView->value,
                    PermissionName::DepositsManage->value,
                    PermissionName::DepositsRefund->value,
                    PermissionName::DepositsDeduct->value,
                    PermissionName::DepositsApprove->value,
                    PermissionName::ChargesView->value,
                    PermissionName::ChargesCreate->value,
                    PermissionName::ChargesApprove->value,
                    PermissionName::ChargesReject->value,
                    PermissionName::ReportsRentalsView->value,
                    PermissionName::ReportsPaymentsView->value,
                    PermissionName::ReportsDepositsView->value,
                    PermissionName::ReportsSalesView->value,
                    PermissionName::ReportsInventoryView->value,
                    PermissionName::ReportsRentalOperationsView->value,
                    PermissionName::ReportsChargesView->value,
                    PermissionName::ReportsExport->value,
                    PermissionName::TripRecommendationsView->value,
                    PermissionName::TripRecommendationsCreate->value,
                    PermissionName::TripRecommendationsUpdate->value,
                    PermissionName::TripRecommendationsDelete->value,
                    PermissionName::GuidesView->value,
                    PermissionName::GuidesCreate->value,
                    PermissionName::GuidesUpdate->value,
                    PermissionName::GuidesPublish->value,
                    PermissionName::GuidesArchive->value,
                    PermissionName::GuidesDelete->value,
                    PermissionName::GuideCategoriesManage->value,
                    PermissionName::SalesView->value,
                    PermissionName::SalesCreate->value,
                    PermissionName::SalesUpdate->value,
                    PermissionName::SalesCancel->value,
                    PermissionName::SalesComplete->value,
                    PermissionName::SalesManage->value,
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
                    PermissionName::RentalsHandover->value,
                    PermissionName::RentalsReturn->value,
                    PermissionName::InspectionsView->value,
                    PermissionName::InspectionsCreate->value,
                    PermissionName::ChargesView->value,
                    PermissionName::ChargesCreate->value,
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
