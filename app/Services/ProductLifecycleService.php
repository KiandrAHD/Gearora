<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalAssetAssignment;
use App\Models\RentalOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Aturan hidup-mati katalog.
 *
 * - Produk/varian tanpa riwayat boleh dihapus permanen.
 * - Riwayat inventaris, order, dan assignment tidak boleh hilang.
 * - Varian yang masih memiliki dependensi hanya dinonaktifkan.
 */
class ProductLifecycleService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @return array{rental_assets:int, stock_movements:int, order_items:int, assignments:int}
     */
    public function productDependencies(Product $product): array
    {
        $variantIds = $product->variants()->pluck('id');
        $assetIds = $variantIds->isEmpty()
            ? collect()
            : RentalAsset::query()->whereIn('product_variant_id', $variantIds)->pluck('id');

        return [
            'rental_assets' => $assetIds->count(),
            'stock_movements' => StockMovement::query()->whereIn('product_variant_id', $variantIds)->count(),
            'order_items' => RentalOrderItem::query()
                ->where(fn ($query) => $query->where('product_id', $product->getKey())
                    ->orWhereIn('product_variant_id', $variantIds))
                ->count(),
            'assignments' => $assetIds->isEmpty()
                ? 0
                : RentalAssetAssignment::query()->whereIn('rental_asset_id', $assetIds)->count(),
        ];
    }

    /**
     * @param  array<string, int>  $dependencies
     */
    public function canDeleteProduct(array $dependencies): bool
    {
        return $this->canDeleteDependencies($dependencies);
    }

    /**
     * @param  array<string, int>  $dependencies
     */
    public function canDeleteDependencies(array $dependencies): bool
    {
        return array_sum($dependencies) === 0;
    }

    /**
     * @return array{rental_assets:int, stock_movements:int, order_items:int, assignments:int}
     */
    public function variantDependencies(ProductVariant $variant): array
    {
        $assetIds = $variant->rentalAssets()->pluck('id');

        return [
            'rental_assets' => $assetIds->count(),
            'stock_movements' => $variant->stockMovements()->count(),
            'order_items' => $variant->orderItems()->count(),
            'assignments' => $assetIds->isEmpty()
                ? 0
                : RentalAssetAssignment::query()->whereIn('rental_asset_id', $assetIds)->count(),
        ];
    }

    public function canDeleteVariant(ProductVariant $variant): bool
    {
        return $this->canDeleteDependencies($this->variantDependencies($variant));
    }

    /**
     * Hapus produk beserta varian/foto hanya saat benar-benar tanpa riwayat.
     *
     * @throws RuntimeException
     */
    public function deleteProduct(Product $product): void
    {
        $mediaPaths = $product->images()->pluck('path')
            ->push($product->thumbnail_path)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $name = $product->name;
        $id = $product->getKey();

        DB::transaction(function () use ($product, $id, $name): void {
            $dependencies = $this->productDependencies($product);

            if (! $this->canDeleteProduct($dependencies)) {
                throw new RuntimeException($this->dependencyMessage($dependencies, 'produk'));
            }

            $product->delete();

            $this->logger->log('product.deleted', null, "Produk {$name} dihapus permanen.", [
                'product_id' => $id,
                'name' => $name,
            ]);
        });

        $disk = Storage::disk(config('gearora.uploads.public_disk'));
        foreach ($mediaPaths as $path) {
            $disk->delete($path);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function deleteVariant(ProductVariant $variant): void
    {
        DB::transaction(function () use ($variant): void {
            if (! $this->canDeleteVariant($variant)) {
                throw new RuntimeException($this->dependencyMessage($this->variantDependencies($variant), 'varian'));
            }

            $sku = $variant->sku;
            $name = $variant->name;

            $variant->delete();

            $this->logger->log('product_variant.deleted', null, "Varian {$name} ({$sku}) dihapus permanen.", [
                'sku' => $sku,
                'name' => $name,
            ]);
        });
    }

    public function deactivateVariant(ProductVariant $variant, string $reason): void
    {
        $variant->forceFill(['is_active' => false])->save();

        $this->logger->log(
            'product_variant.deactivated',
            $variant,
            "Varian {$variant->sku} dinonaktifkan: {$reason}.",
        );
    }

    /**
     * @param  array<string, int>  $dependencies
     */
    public function dependencyMessage(array $dependencies, string $subject): string
    {
        $labels = [
            'rental_assets' => 'unit rental',
            'stock_movements' => 'riwayat stok',
            'order_items' => 'riwayat pesanan',
            'assignments' => 'riwayat pemakaian unit',
        ];

        $parts = [];
        foreach ($labels as $key => $label) {
            if (($dependencies[$key] ?? 0) > 0) {
                $parts[] = $dependencies[$key].' '.$label;
            }
        }

        return ucfirst($subject).' tidak dapat dihapus permanen karena masih memiliki '.implode(', ', $parts).'.';
    }
}
