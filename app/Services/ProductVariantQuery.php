<?php

namespace App\Services;

use App\Enums\SalesReservationStatus;
use Illuminate\Support\Facades\DB;

/**
 * Query ringkas inventaris untuk tabel laporan (dipisah agar mudah diuji).
 */
final class ProductVariantQuery
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rows(): array
    {
        return DB::table('product_variants as variants')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->leftJoin('sales_stock_reservations as reservations', function ($join) {
                $join->on('reservations.product_variant_id', '=', 'variants.id')
                    ->where('reservations.status', '=', SalesReservationStatus::Reserved->value);
            })
            ->groupBy('variants.id', 'products.name', 'variants.name', 'variants.sku', 'variants.stock_quantity')
            ->orderBy('products.name')
            ->get([
                'products.name as product',
                'variants.name as variant',
                'variants.sku',
                'variants.stock_quantity',
                DB::raw('COALESCE(SUM(reservations.quantity), 0) as reserved'),
            ])
            ->map(fn ($row) => [
                'product' => $row->product,
                'variant' => $row->variant,
                'sku' => $row->sku,
                'stock' => (int) $row->stock_quantity,
                'reserved' => (int) $row->reserved,
                'available' => max(0, (int) $row->stock_quantity - (int) $row->reserved),
            ])
            ->all();
    }
}
