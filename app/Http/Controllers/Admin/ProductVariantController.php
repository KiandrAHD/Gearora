<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use App\Services\ProductLifecycleService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ProductLifecycleService $lifecycle,
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
        $newStock = $data['stock_quantity'] ?? null;

        $variant->fill([
            'name' => $data['name'],
            'sku' => strtoupper($data['sku']),
            'rental_price_per_day' => $data['rental_price_per_day'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'is_active' => $request->boolean('is_active', $variant->is_active),
            'sort_order' => $data['sort_order'] ?? $variant->sort_order,
        ])->save();

        if ($newStock !== null && (int) $newStock !== $variant->stock_quantity) {
            $this->inventory->setSaleStock(
                $variant,
                (int) $newStock,
                StockMovementType::Adjustment,
                'Perubahan stok dari editor varian',
                $request->user(),
            );
        }

        $this->logger->log('product_variant.updated', $variant, "Varian {$variant->sku} diperbarui.");

        return redirect()->route('admin.products.edit', $product)->with('status', 'Varian diperbarui.');
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->getKey(), 404);

        $dependencies = $this->lifecycle->variantDependencies($variant);

        if (! $this->lifecycle->canDeleteDependencies($dependencies)) {
            $this->lifecycle->deactivateVariant($variant, 'varian masih memiliki dependensi inventaris/pesanan');

            $units = $dependencies['rental_assets'];
            $message = $units > 0
                ? "Varian tidak dihapus permanen karena masih memiliki {$units} unit rental. Varian berhasil dinonaktifkan dan tidak lagi tersedia untuk pemesanan baru."
                : 'Varian tidak dihapus permanen karena masih memiliki riwayat inventaris/pesanan. Varian berhasil dinonaktifkan dan tidak lagi tersedia untuk pemesanan baru.';

            return redirect()->route('admin.products.edit', $product)->with('status', $message);
        }

        $sku = $variant->sku;

        try {
            $this->lifecycle->deleteVariant($variant);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.products.edit', $product)
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.products.edit', $product)->with('status', "Varian {$sku} dihapus permanen.");
    }
}
