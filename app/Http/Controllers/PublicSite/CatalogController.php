<?php

namespace App\Http\Controllers\PublicSite;

use App\Enums\ProductStatus;
use App\Enums\SalesReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\CatalogFilterRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\RentalAvailabilityService;
use App\Support\RentalPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Throwable;

class CatalogController extends Controller
{
    /**
     * Kolom harga efektif per mode. Varian boleh mengosongkan harga; saat itu
     * harga produk menjadi fallback (COALESCE), sama seperti model varian.
     */
    private const MODE_PRICE_COLUMN = [
        'sewa' => 'rental_price_per_day',
        'beli' => 'sale_price',
    ];

    public function __construct(private readonly RentalAvailabilityService $availability) {}

    public function index(CatalogFilterRequest $request): View
    {
        $validated = $request->validated();
        $mode = $request->mode();
        $isRetail = $request->isRetail();

        $keyword = (string) ($validated['q'] ?? '');
        $categorySlug = (string) ($validated['kategori'] ?? '');
        $sort = (string) ($validated['sort'] ?? '');
        $minPrice = isset($validated['min_price']) ? (int) $validated['min_price'] : null;
        $maxPrice = isset($validated['max_price']) ? (int) $validated['max_price'] : null;
        $stockOnly = $isRetail && ($validated['stock'] ?? null) === 'tersedia';

        $startDate = $isRetail ? '' : (string) ($validated['start_date'] ?? '');
        $endDate = $isRetail ? '' : (string) ($validated['end_date'] ?? '');

        $priceColumn = self::MODE_PRICE_COLUMN[$mode];
        $effective = "COALESCE(product_variants.{$priceColumn}, products.{$priceColumn})";

        $query = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->with([
                'category',
                'variants' => fn ($inner) => $inner->active()->orderBy('sort_order')->orderBy('id'),
                'primaryImage',
            ]);

        if ($mode === 'sewa') {
            $query->rental();
        } else {
            $query->sale();
        }

        // Hanya produk yang punya varian aktif dengan harga valid untuk mode ini.
        $query->whereHas('variants', fn ($inner) => $inner
            ->where('is_active', true)
            ->whereRaw("{$effective} > 0"));

        if ($keyword !== '') {
            $query->where(function (Builder $inner) use ($keyword) {
                // LOWER membuat pencarian konsisten pada MySQL dan SQLite,
                // tidak bergantung pada collation production.
                $needle = mb_strtolower($keyword);
                $inner->whereRaw('LOWER(name) like ?', ['%'.$needle.'%'])
                    ->orWhereRaw('LOWER(description) like ?', ['%'.$needle.'%'])
                    ->orWhereRaw('LOWER(brand) like ?', ['%'.$needle.'%']);
            });
        }

        if ($categorySlug !== '') {
            $query->whereHas('category', fn ($inner) => $inner->where('slug', $categorySlug));
        }

        if ($minPrice !== null) {
            $query->whereHas('variants', fn ($inner) => $inner
                ->where('is_active', true)
                ->whereRaw("{$effective} >= ?", [$minPrice]));
        }

        if ($maxPrice !== null && ($minPrice === null || $maxPrice >= $minPrice)) {
            $query->whereHas('variants', fn ($inner) => $inner
                ->where('is_active', true)
                ->whereRaw("{$effective} <= ?", [$maxPrice]));
        }

        if ($stockOnly) {
            $query->whereHas('variants', fn ($inner) => $inner
                ->where('is_active', true)
                ->whereRaw(
                    'product_variants.stock_quantity > (select coalesce(sum(r.quantity), 0) '
                    .'from sales_stock_reservations r '
                    .'where r.product_variant_id = product_variants.id '
                    .'and r.status = ? and r.expires_at > ?)',
                    [SalesReservationStatus::Reserved->value, now()],
                ));
        }

        $dateNotice = null;
        $period = null;

        if (! $isRetail && $startDate !== '' && $endDate !== '') {
            try {
                $period = RentalPeriod::from($startDate, $endDate);
                $period->assertValid(config('gearora.rental'));

                $availableVariantIds = $this->availability->availableVariantIds($period);

                if ($availableVariantIds === []) {
                    $query->whereRaw('1 = 0');
                    $dateNotice = 'Tidak ada unit rental yang tersedia pada tanggal tersebut. Coba tanggal lain.';
                } else {
                    $query->whereHas('variants', fn ($inner) => $inner->whereIn('id', $availableVariantIds));
                }
            } catch (Throwable $exception) {
                $period = null;
                $dateNotice = $exception->getMessage();
            }
        }

        $this->applySort($query, $sort, $effective);

        return view('catalog.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()->active()->orderBy('sort_order')->get(),
            'keyword' => $keyword,
            'categorySlug' => $categorySlug,
            'mode' => $mode,
            'sort' => $sort,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'stockOnly' => $stockOnly,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sorts' => [
                'unggulan' => 'Unggulan & terbaru',
                'terbaru' => 'Produk terbaru',
                'nama' => 'Nama A–Z',
                'harga_terendah' => $isRetail ? 'Harga terendah' : 'Sewa terendah',
                'harga_tertinggi' => $isRetail ? 'Harga tertinggi' : 'Sewa tertinggi',
            ],
            'dateNotice' => $dateNotice,
            'period' => $period,
        ]);
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort, string $effective): void
    {
        match ($sort) {
            'nama' => $query->orderBy('name')->orderBy('id'),
            'harga_terendah' => $query->orderByRaw(
                "(select min({$effective}) from product_variants "
                .'where product_variants.product_id = products.id '
                ."and product_variants.is_active = 1 and {$effective} > 0) asc"
            )->orderBy('id'),
            'harga_tertinggi' => $query->orderByRaw(
                "(select max({$effective}) from product_variants "
                .'where product_variants.product_id = products.id '
                ."and product_variants.is_active = 1 and {$effective} > 0) desc"
            )->orderBy('id'),
            'terbaru' => $query->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->orderByDesc('is_featured')->orderByDesc('created_at')->orderByDesc('id'),
        };
    }
}
