<?php

namespace App\Services;

use App\Enums\RentalAssetStatus;
use App\Enums\StockMovementType;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Perubahan stok jual dan unit rental selalu menghasilkan stock movement.
 * Tidak ada perubahan stok tanpa jejak.
 */
class InventoryService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRentalAsset(ProductVariant $variant, array $data, ?User $actor = null): RentalAsset
    {
        return DB::transaction(function () use ($variant, $data, $actor) {
            $asset = $variant->rentalAssets()->create([
                'asset_code' => $data['asset_code'],
                'condition' => $data['condition'] ?? 'good',
                'status' => $data['status'] ?? RentalAssetStatus::Available,
                'purchase_date' => $data['purchase_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->record($variant, StockMovementType::Initial, [
                'rental_asset_id' => $asset->getKey(),
                'status_after' => $asset->status->value,
                'reason' => 'Unit rental baru terdaftar',
                'user_id' => $actor?->getKey(),
            ]);

            $this->logger->log(
                'inventory.asset_created',
                $asset,
                "Unit rental {$asset->asset_code} ditambahkan ke varian {$variant->name}.",
                ['asset_code' => $asset->asset_code, 'variant_id' => $variant->getKey()],
                $actor,
            );

            return $asset;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRentalAsset(RentalAsset $asset, array $data, ?User $actor = null): RentalAsset
    {
        return DB::transaction(function () use ($asset, $data, $actor) {
            $statusBefore = $asset->status;
            $conditionBefore = $asset->condition;

            $asset->fill([
                'asset_code' => $data['asset_code'] ?? $asset->asset_code,
                'condition' => $data['condition'] ?? $asset->condition,
                'status' => $data['status'] ?? $asset->status,
                'purchase_date' => $data['purchase_date'] ?? $asset->purchase_date,
                'notes' => $data['notes'] ?? $asset->notes,
            ])->save();

            if ($statusBefore !== $asset->status) {
                $this->record($asset->variant, $this->statusMovementType($asset->status), [
                    'rental_asset_id' => $asset->getKey(),
                    'status_before' => $statusBefore->value,
                    'status_after' => $asset->status->value,
                    'reason' => $data['reason'] ?? 'Perubahan status unit',
                    'user_id' => $actor?->getKey(),
                ]);

                $this->logger->log(
                    'inventory.asset_status_changed',
                    $asset,
                    "Status unit {$asset->asset_code}: {$statusBefore->value} -> {$asset->status->value}.",
                    ['from' => $statusBefore->value, 'to' => $asset->status->value],
                    $actor,
                );
            }

            if ($conditionBefore !== $asset->condition) {
                $this->logger->log(
                    'inventory.asset_condition_changed',
                    $asset,
                    "Kondisi unit {$asset->asset_code}: {$conditionBefore->value} -> {$asset->condition->value}.",
                    ['from' => $conditionBefore->value, 'to' => $asset->condition->value],
                    $actor,
                );
            }

            return $asset->refresh();
        });
    }

    /**
     * Setel stok jual varian ke nilai absolut dan catat movement.
     */
    public function setSaleStock(
        ProductVariant $variant,
        int $newQuantity,
        StockMovementType $type,
        string $reason,
        ?User $actor = null,
    ): ProductVariant {
        if ($newQuantity < 0) {
            throw new InvalidArgumentException('Stok jual tidak boleh negatif.');
        }

        return DB::transaction(function () use ($variant, $newQuantity, $type, $reason, $actor) {
            $before = $variant->stock_quantity;

            $variant->forceFill(['stock_quantity' => $newQuantity])->save();

            $this->record($variant, $type, [
                'quantity_before' => $before,
                'quantity_after' => $newQuantity,
                'reason' => $reason,
                'user_id' => $actor?->getKey(),
            ]);

            $this->logger->log(
                'inventory.sale_stock_adjusted',
                $variant,
                "Stok jual {$variant->sku}: {$before} -> {$newQuantity} ({$reason}).",
                ['from' => $before, 'to' => $newQuantity, 'reason' => $reason],
                $actor,
            );

            return $variant->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(ProductVariant $variant, StockMovementType $type, array $attributes): StockMovement
    {
        return StockMovement::query()->create(array_merge([
            'product_variant_id' => $variant->getKey(),
            'type' => $type,
            'created_at' => now(),
        ], $attributes));
    }

    private function statusMovementType(RentalAssetStatus $status): StockMovementType
    {
        return match ($status) {
            RentalAssetStatus::Damaged => StockMovementType::Damage,
            RentalAssetStatus::Lost => StockMovementType::Lost,
            RentalAssetStatus::Rented => StockMovementType::RentalOut,
            default => StockMovementType::Adjustment,
        };
    }
}
