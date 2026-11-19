<?php

namespace App\Support\Navigation;

use App\Models\User;

/**
 * Konfigurasi navigasi Admin (main menu + submenu).
 *
 * Struktur terpusat agar sidebar tidak lagi berupa daftar datar, mudah diuji,
 * dan setiap item terikat pada permission existing.
 */
final class AdminNavigation
{
    /**
     * Permission yang menandakan akses ke salah satu laporan.
     *
     * @return array<int, string>
     */
    public static function reportPermissions(): array
    {
        return [
            'reports.rentals.view',
            'reports.sales.view',
            'reports.payments.view',
            'reports.deposits.view',
            'reports.charges.view',
            'reports.inventory.view',
            'reports.rental_operations.view',
        ];
    }

    /**
     * Definisi lengkap grup navigasi (belum difilter permission).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function groups(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'dashboard',
                'standalone' => true,
                'route' => 'admin.dashboard',
                'match' => 'admin.dashboard',
                'permission' => 'dashboard.view',
                'children' => [],
            ],
            [
                'id' => 'catalog',
                'label' => 'Katalog & Inventaris',
                'icon' => 'catalog',
                'children' => [
                    ['label' => 'Kategori', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'permission' => 'categories.view'],
                    ['label' => 'Produk & Varian', 'route' => 'admin.products.index', 'match' => 'admin.products.*', 'permission' => 'products.view'],
                    ['label' => 'Inventaris', 'route' => 'admin.inventory.index', 'match' => 'admin.inventory.*', 'permission' => 'inventory.view'],
                ],
            ],
            [
                'id' => 'rental',
                'label' => 'Operasional Rental',
                'icon' => 'operations',
                'children' => [
                    ['label' => 'Pesanan Rental', 'route' => 'admin.rentals.index', 'match' => 'admin.rentals.*', 'permission' => 'rentals.view'],
                    ['label' => 'Pickup', 'route' => 'admin.operations.pickups', 'match' => 'admin.operations.pickups*', 'permission' => 'rentals.handover', 'badge' => 'pickups_today'],
                    ['label' => 'Pengembalian', 'route' => 'admin.operations.returns', 'match' => 'admin.operations.returns*', 'permission' => 'rentals.return', 'badge' => 'returns_today'],
                    ['label' => 'Inspeksi', 'route' => 'admin.operations.inspections', 'match' => 'admin.operations.inspections*', 'permission' => 'inspections.view', 'badge' => 'inspections_pending'],
                    ['label' => 'Overdue', 'route' => 'admin.operations.overdue', 'match' => 'admin.operations.overdue*', 'permission' => 'rentals.view', 'badge' => 'overdue'],
                ],
            ],
            [
                'id' => 'retail',
                'label' => 'Penjualan Retail',
                'icon' => 'sales',
                'children' => [
                    ['label' => 'Pesanan Retail', 'route' => 'admin.sales.index', 'match' => 'admin.sales.*', 'permission' => 'sales.view'],
                ],
            ],
            [
                'id' => 'finance',
                'label' => 'Keuangan',
                'icon' => 'finance',
                'children' => [
                    ['label' => 'Pembayaran', 'route' => 'admin.payments.index', 'match' => 'admin.payments.*', 'permission' => 'payments.view', 'badge' => 'payments_waiting'],
                    ['label' => 'Refund', 'route' => 'admin.refunds.index', 'match' => 'admin.refunds.*', 'permission' => 'payments.refund'],
                    ['label' => 'Deposit', 'route' => 'admin.deposits.index', 'match' => 'admin.deposits.*', 'permission' => 'deposits.view'],
                    ['label' => 'Biaya Tambahan', 'route' => 'admin.charges.index', 'match' => 'admin.charges.*', 'permission' => 'charges.view', 'badge' => 'charges_pending'],
                ],
            ],
            [
                'id' => 'customers',
                'label' => 'Pelanggan',
                'icon' => 'customers',
                'children' => [
                    ['label' => 'Data Pelanggan', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'permission' => 'customers.view'],
                ],
            ],
            [
                'id' => 'content',
                'label' => 'Konten & Rekomendasi',
                'icon' => 'content',
                'children' => [
                    ['label' => 'Panduan', 'route' => 'admin.guides.index', 'match' => 'admin.guides.*', 'permission' => 'guides.view'],
                    ['label' => 'Kategori Panduan', 'route' => 'admin.guide-categories.index', 'match' => 'admin.guide-categories.*', 'permission' => 'guide_categories.manage'],
                    ['label' => 'Aturan Rekomendasi Trip', 'route' => 'admin.trip-recommendations.index', 'match' => 'admin.trip-recommendations.*', 'permission' => 'trip_recommendations.view'],
                ],
            ],
            [
                'id' => 'reports',
                'label' => 'Laporan',
                'icon' => 'reports',
                'children' => [
                    ['label' => 'Ringkasan', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'report' => 'summary', 'any' => self::reportPermissions()],
                    ['label' => 'Rental', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'report' => 'rental', 'permission' => 'reports.rentals.view'],
                    ['label' => 'Retail', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'report' => 'sales', 'permission' => 'reports.sales.view'],
                    ['label' => 'Pembayaran', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'report' => 'payments', 'permission' => 'reports.payments.view'],
                    ['label' => 'Deposit', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'report' => 'deposits', 'permission' => 'reports.deposits.view'],
                    ['label' => 'Biaya Tambahan', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'report' => 'charges', 'permission' => 'reports.charges.view'],
                    ['label' => 'Inventaris', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'report' => 'inventory', 'permission' => 'reports.inventory.view'],
                    ['label' => 'Operasional', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'report' => 'operations', 'permission' => 'reports.rental_operations.view'],
                ],
            ],
            [
                'id' => 'system',
                'label' => 'Sistem',
                'icon' => 'system',
                'children' => [
                    ['label' => 'Log Aktivitas', 'route' => 'admin.activity.index', 'match' => 'admin.activity.*', 'permission' => 'dashboard.view'],
                ],
            ],
        ];
    }

    /**
     * Menu untuk pengguna tertentu, sudah difilter permission.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $groups = [];

        foreach (self::groups() as $group) {
            if (! empty($group['standalone'])) {
                if (self::allows($user, $group['permission'])) {
                    $groups[] = $group;
                }

                continue;
            }

            $children = array_values(array_filter(
                $group['children'],
                fn (array $child) => self::allowsChild($user, $child),
            ));

            if ($children === []) {
                continue;
            }

            $group['children'] = $children;
            $groups[] = $group;
        }

        return $groups;
    }

    public static function allows(?User $user, ?string $permission): bool
    {
        if ($user === null || $permission === null) {
            return false;
        }

        return $user->isOwner() || $user->hasPermission($permission);
    }

    /**
     * @param  array<string, mixed>  $child
     */
    public static function allowsChild(User $user, array $child): bool
    {
        if (! empty($child['any'])) {
            return $user->isOwner() || $user->hasAnyPermission($child['any']);
        }

        return self::allows($user, $child['permission'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $item  Grup atau child.
     */
    public static function isActive(array $item): bool
    {
        if (empty($item['match'])) {
            return false;
        }

        if (! request()->routeIs($item['match'])) {
            return false;
        }

        if (array_key_exists('report', $item)) {
            return self::activeReport() === $item['report'];
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    public static function isGroupActive(array $group): bool
    {
        if (! empty($group['standalone'])) {
            return self::isActive($group);
        }

        foreach ($group['children'] as $child) {
            if (self::isActive($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Report yang sedang aktif pada halaman laporan.
     */
    public static function activeReport(): string
    {
        $report = (string) request()->query('report', 'summary');

        return array_key_exists($report, self::reportPermissionsByName()) ? $report : 'summary';
    }

    /**
     * @return array<string, string>
     */
    public static function reportPermissionsByName(): array
    {
        return [
            'summary' => '',
            'rental' => 'reports.rentals.view',
            'sales' => 'reports.sales.view',
            'payments' => 'reports.payments.view',
            'deposits' => 'reports.deposits.view',
            'charges' => 'reports.charges.view',
            'inventory' => 'reports.inventory.view',
            'operations' => 'reports.rental_operations.view',
        ];
    }
}
