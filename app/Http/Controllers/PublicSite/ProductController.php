<?php

namespace App\Http\Controllers\PublicSite;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RentalAvailabilityService;
use App\Services\RentalPricingService;
use App\Services\SalesStockService;
use App\Support\RentalPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly RentalAvailabilityService $availability,
        private readonly RentalPricingService $pricing,
        private readonly SalesStockService $stock,
    ) {}

    public function show(Request $request, Product $product): View
    {
        abort_unless($product->status === ProductStatus::Active, 404);

        $product->load([
            'category',
            'variants' => fn ($query) => $query->where('is_active', true),
            'variants.rentalAssets',
            'images',
        ]);

        $startDate = (string) $request->query('start_date');
        $endDate = (string) $request->query('end_date');
        $availability = [];
        $quote = null;
        $dateNotice = null;

        if ($product->supportsRental() && $startDate !== '' && $endDate !== '') {
            try {
                $period = RentalPeriod::from($startDate, $endDate);
                $period->assertValid(config('gearora.rental'));

                $rentalVariants = $product->variants->filter(
                    fn ($variant) => $variant->effectiveRentalPrice() !== null
                        && (float) $variant->effectiveRentalPrice() > 0
                );

                foreach ($rentalVariants as $variant) {
                    $result = $this->availability->check($variant, 1, $period);
                    $availability[$variant->getKey()] = $result;
                }

                if ($rentalVariants->isNotEmpty()) {
                    $quote = $this->pricing->quote(
                        [['variant' => $rentalVariants->first(), 'quantity' => 1]],
                        $period,
                    );
                }
            } catch (Throwable $exception) {
                $dateNotice = $exception->getMessage();
            }
        }

        // Stok retail = fisik - reservasi aktif, dihitung via service (bukan stok fisik).
        $saleAvailability = [];
        foreach ($product->variants as $variant) {
            $salePrice = $variant->effectiveSalePrice();
            if ($product->supportsSale() && $salePrice !== null && (float) $salePrice > 0) {
                $saleAvailability[$variant->getKey()] = $this->stock->availableQuantity($variant);
            }
        }

        $related = Product::query()
            ->active()
            ->with(['category', 'variants', 'primaryImage'])
            ->where('id', '!=', $product->getKey())
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->take(4)
            ->get();

        return view('catalog.show', [
            'product' => $product,
            'availability' => $availability,
            'quote' => $quote,
            'dateNotice' => $dateNotice,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'saleAvailability' => $saleAvailability,
            'related' => $related,
        ]);
    }
}
