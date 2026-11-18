<?php

namespace App\Enums;

enum PermissionName: string
{
    case DashboardView = 'dashboard.view';

    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersManageRoles = 'users.manage_roles';

    case CategoriesView = 'categories.view';
    case CategoriesCreate = 'categories.create';
    case CategoriesUpdate = 'categories.update';
    case CategoriesDelete = 'categories.delete';

    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsArchive = 'products.archive';
    case ProductsDelete = 'products.delete';

    case InventoryView = 'inventory.view';
    case InventoryManage = 'inventory.manage';
    case InventoryAdjustStock = 'inventory.adjust_stock';
    case InventoryManageAssets = 'inventory.manage_assets';

    case RentalsView = 'rentals.view';
    case RentalsCreate = 'rentals.create';
    case RentalsUpdate = 'rentals.update';
    case RentalsCancel = 'rentals.cancel';

    case PaymentsView = 'payments.view';
    case PaymentsVerify = 'payments.verify';
    case PaymentsReject = 'payments.reject';
    case PaymentsRefund = 'payments.refund';
    case PaymentsSettingsManage = 'payments.settings.manage';

    case RentalsHandover = 'rentals.handover';
    case RentalsReturn = 'rentals.return';
    case RentalsComplete = 'rentals.complete';

    case InspectionsView = 'inspections.view';
    case InspectionsCreate = 'inspections.create';
    case InspectionsApprove = 'inspections.approve';

    case DepositsView = 'deposits.view';
    case DepositsManage = 'deposits.manage';
    case DepositsRefund = 'deposits.refund';
    case DepositsDeduct = 'deposits.deduct';
    case DepositsApprove = 'deposits.approve';

    case ChargesView = 'charges.view';
    case ChargesCreate = 'charges.create';
    case ChargesApprove = 'charges.approve';
    case ChargesReject = 'charges.reject';

    case ReportsRentalsView = 'reports.rentals.view';
    case ReportsPaymentsView = 'reports.payments.view';
    case ReportsDepositsView = 'reports.deposits.view';
    case ReportsSalesView = 'reports.sales.view';
    case ReportsInventoryView = 'reports.inventory.view';
    case ReportsRentalOperationsView = 'reports.rental_operations.view';
    case ReportsExport = 'reports.export';

    case TripRecommendationsView = 'trip_recommendations.view';
    case TripRecommendationsCreate = 'trip_recommendations.create';
    case TripRecommendationsUpdate = 'trip_recommendations.update';
    case TripRecommendationsDelete = 'trip_recommendations.delete';

    case GuidesView = 'guides.view';
    case GuidesCreate = 'guides.create';
    case GuidesUpdate = 'guides.update';
    case GuidesPublish = 'guides.publish';
    case GuidesArchive = 'guides.archive';
    case GuidesDelete = 'guides.delete';
    case GuideCategoriesManage = 'guide_categories.manage';

    case SalesView = 'sales.view';
    case SalesCreate = 'sales.create';
    case SalesUpdate = 'sales.update';
    case SalesCancel = 'sales.cancel';
    case SalesComplete = 'sales.complete';
    case SalesManage = 'sales.manage';

    case CustomersView = 'customers.view';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'Melihat dashboard',
            self::UsersView => 'Melihat pengguna',
            self::UsersCreate => 'Membuat pengguna',
            self::UsersUpdate => 'Mengubah pengguna',
            self::UsersManageRoles => 'Mengatur role & permission',
            self::CategoriesView => 'Melihat kategori',
            self::CategoriesCreate => 'Membuat kategori',
            self::CategoriesUpdate => 'Mengubah kategori',
            self::CategoriesDelete => 'Menonaktifkan kategori',
            self::ProductsView => 'Melihat produk',
            self::ProductsCreate => 'Membuat produk',
            self::ProductsUpdate => 'Mengubah produk',
            self::ProductsArchive => 'Mengarsipkan produk',
            self::ProductsDelete => 'Menghapus produk permanen',
            self::InventoryView => 'Melihat inventaris',
            self::InventoryManage => 'Mengelola inventaris',
            self::InventoryAdjustStock => 'Menyesuaikan stok',
            self::InventoryManageAssets => 'Mengelola unit rental',
            self::RentalsView => 'Melihat rental order',
            self::RentalsCreate => 'Membuat rental order',
            self::RentalsUpdate => 'Mengubah rental order',
            self::RentalsCancel => 'Membatalkan rental order',
            self::PaymentsView => 'Melihat pembayaran',
            self::PaymentsVerify => 'Memverifikasi pembayaran',
            self::PaymentsReject => 'Menolak pembayaran',
            self::PaymentsRefund => 'Memproses refund pembayaran',
            self::PaymentsSettingsManage => 'Mengelola pengaturan pembayaran',
            self::RentalsHandover => 'Melakukan serah terima rental',
            self::RentalsReturn => 'Menerima pengembalian rental',
            self::RentalsComplete => 'Menyelesaikan rental',
            self::InspectionsView => 'Melihat inspeksi',
            self::InspectionsCreate => 'Membuat inspeksi',
            self::InspectionsApprove => 'Menyetujui hasil inspeksi',
            self::DepositsView => 'Melihat deposit',
            self::DepositsManage => 'Mengelola deposit',
            self::DepositsRefund => 'Mengembalikan deposit',
            self::DepositsDeduct => 'Memotong deposit',
            self::DepositsApprove => 'Menyetujui transaksi deposit',
            self::ChargesView => 'Melihat biaya tambahan',
            self::ChargesCreate => 'Membuat biaya tambahan',
            self::ChargesApprove => 'Menyetujui biaya tambahan',
            self::ChargesReject => 'Menolak biaya tambahan',
            self::ReportsRentalsView => 'Melihat laporan rental',
            self::ReportsPaymentsView => 'Melihat laporan pembayaran',
            self::ReportsDepositsView => 'Melihat laporan deposit',
            self::ReportsSalesView => 'Melihat laporan penjualan',
            self::ReportsInventoryView => 'Melihat laporan inventaris',
            self::ReportsRentalOperationsView => 'Melihat laporan operasional rental',
            self::ReportsExport => 'Ekspor laporan',
            self::TripRecommendationsView => 'Melihat aturan rekomendasi trip',
            self::TripRecommendationsCreate => 'Membuat aturan rekomendasi trip',
            self::TripRecommendationsUpdate => 'Mengubah aturan rekomendasi trip',
            self::TripRecommendationsDelete => 'Menghapus aturan rekomendasi trip',
            self::GuidesView => 'Melihat panduan',
            self::GuidesCreate => 'Membuat panduan',
            self::GuidesUpdate => 'Mengubah panduan',
            self::GuidesPublish => 'Menerbitkan panduan',
            self::GuidesArchive => 'Mengarsipkan panduan',
            self::GuidesDelete => 'Menghapus panduan',
            self::GuideCategoriesManage => 'Mengelola kategori panduan',
            self::SalesView => 'Melihat sales order',
            self::SalesCreate => 'Membuat sales order',
            self::SalesUpdate => 'Mengubah sales order',
            self::SalesCancel => 'Membatalkan sales order',
            self::SalesComplete => 'Menyelesaikan sales order',
            self::SalesManage => 'Mengelola sales order',
            self::CustomersView => 'Melihat pelanggan',
        };
    }

    public function group(): string
    {
        return explode('.', $this->value)[0];
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
