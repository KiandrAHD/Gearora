<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ActivityLogger $logger,
    ) {}

    public function store(ProductVariantRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        $variant = $product->variants()->create([
            'name' => $data['name'],
            'sku' => strtoupper($data['sku']),
            'rental_price_per_day' => $data['rental_price_per_day'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'stock_quantity' => 0,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if (! empty($data['stock_quantity'])) {
            $this->inventory->setSaleStock(
                $variant,
                (int) $data['stock_quantity'],
                StockMovementType::Initial,
                'Stok awal saat varian dibuat',
                $request->user(),
            );
        }

        $this->logger->log('product_variant.created', $variant, "Varian {$variant->name} ({$variant->sku}) dibuat untuk {$product->name}.");

        return redirect()->route('admin.products.edit', $product)->with('status', 'Varian ditambahkan.');
    }

    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->getKey(), 404);

        $data = $request->validated();

        $variant->fill([
            'name' => $data['name'],
            'sku' => strtoupper($data['sku']),
            'rental_price_per_day' => $data['rental_price_per_day'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'is_active' => $request->boolean('is_active', $variant->is_active),
            'sort_order' => $data['sort_order'] ?? $variant->sort_order,
        ])->save();

        $this->logger->log('product_variant.updated', $variant, "Varian {$variant->sku} diperbarui.");

        return redirect()->route('admin.products.edit', $product)->with('status', 'Varian diperbarui.');
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->getKey(), 404);

        if ($variant->rentalAssets()->exists() || $variant->stockMovements()->exists()) {
            $variant->forceFill(['is_active' => false])->save();
            $this->logger->log('product_variant.deactivated', $variant, "Varian {$variant->sku} dinonaktifkan karena memiliki riwayat.");

            return redirect()->route('admin.products.edit', $product)
                ->with('status', 'Varian memiliki riwayat stok/unit sehingga dinonaktifkan, bukan dihapus.');
        }

        $sku = $variant->sku;
        $variant->delete();
        $this->logger->log('product_variant.deleted', $product, "Varian {$sku} dihapus.", ['sku' => $sku]);

        return redirect()->route('admin.products.edit', $product)->with('status', 'Varian dihapus.');
    }
}
