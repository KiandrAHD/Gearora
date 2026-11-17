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
