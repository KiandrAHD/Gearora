<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Keranjang retail, terpisah dari draft booking rental
 * (`rental_booking_draft`). Session key khusus retail: `retail_cart`.
 *
 * Keranjang hanya menyimpan variant_id + quantity. Harga TIDAK pernah dipercaya
 * dari klien; harga & stok dihitung ulang dari database saat ditampilkan dan
 * saat checkout.
 */
class SalesCartService
{
    public const SESSION_KEY = 'retail_cart';

    public function __construct(private readonly SalesStockService $stock) {}

    /** @return array<int, int> variant_id => quantity */
    public function raw(): array
    {
        $data = session(self::SESSION_KEY, []);

        if (! is_array($data)) {
            return [];
        }

        $clean = [];

        foreach ($data as $variantId => $quantity) {
            $variantId = (int) $variantId;
            $quantity = (int) $quantity;

            if ($variantId > 0 && $quantity > 0) {
                $clean[$variantId] = $quantity;
            }
        }

        return $clean;
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    public function add(ProductVariant $variant, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Jumlah minimal 1.');
        }

        $this->assertPurchasable($variant);

        $cart = $this->raw();
        $target = ($cart[$variant->getKey()] ?? 0) + $quantity;

        $this->assertStock($variant, $target);

        $cart[$variant->getKey()] = $target;
        $this->persist($cart);
    }

    public function update(ProductVariant $variant, int $quantity): void
    {
        $cart = $this->raw();

        if ($quantity <= 0) {
            unset($cart[$variant->getKey()]);
            $this->persist($cart);

            return;
        }

        $this->assertPurchasable($variant);
        $this->assertStock($variant, $quantity);

        $cart[$variant->getKey()] = $quantity;
        $this->persist($cart);
    }

    public function remove(int $variantId): void
    {
        $cart = $this->raw();
        unset($cart[$variantId]);
        $this->persist($cart);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Baris keranjang yang sudah divalidasi ulang terhadap database.
     *
     * @return Collection<int, array{variant:ProductVariant, product:Product, quantity:int, unit_price:int, line_total:int, available:int}>
     */
    public function lines(): Collection
    {
        $cart = $this->raw();

        if ($cart === []) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', array_keys($cart))
            ->orderBy('id')
            ->get();

        return $variants
            ->map(function (ProductVariant $variant) use ($cart) {
                $product = $variant->product;

                if ($product === null || $product->status !== ProductStatus::Active || ! $product->supportsSale() || ! $variant->is_active) {
                    return null;
                }

                $unitPrice = Money::toInt($variant->effectiveSalePrice());

                if ($unitPrice <= 0) {
                    return null;
                }

                $quantity = (int) $cart[$variant->getKey()];

                return [
                    'variant' => $variant,
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $quantity,
                    'available' => $this->stock->availableQuantity($variant),
                ];
            })
            ->filter()
            ->values();
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum('line_total');
    }

    /**
     * Item yang siap checkout (kuantitas tersedia mencukupi).
     *
     * @return array<int, array{variant_id:int, quantity:int}>
     */
    public function checkoutItems(): array
    {
        return $this->lines()
            ->map(fn (array $line) => [
                'variant_id' => $line['variant']->getKey(),
                'quantity' => $line['quantity'],
            ])
            ->values()
            ->all();
    }

    /** @param array<int, int> $cart */
    private function persist(array $cart): void
    {
        session([self::SESSION_KEY => $cart]);
    }

    private function assertPurchasable(ProductVariant $variant): void
    {
        $product = $variant->product;

        if ($product === null || $product->status !== ProductStatus::Active) {
            throw new RuntimeException('Produk tidak tersedia.');
        }

        if (! $product->supportsSale()) {
            throw new RuntimeException("Produk {$product->name} hanya tersedia untuk disewa.");
        }

        if (! $variant->is_active) {
            throw new RuntimeException("Varian {$variant->name} sudah tidak aktif.");
        }

        if (Money::toInt($variant->effectiveSalePrice()) <= 0) {
            throw new RuntimeException("Harga jual varian {$variant->name} belum valid.");
        }
    }

    private function assertStock(ProductVariant $variant, int $quantity): void
    {
        $available = $this->stock->availableQuantity($variant);

        if ($quantity > $available) {
            throw new RuntimeException("Stok {$variant->product?->name} - {$variant->name} tersisa {$available}.");
        }
    }
}
