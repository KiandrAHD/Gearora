<?php

namespace App\Http\Controllers\PublicSite;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\RentalAvailabilityService;
use App\Support\RentalPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CatalogController extends Controller
{
    public function __construct(private readonly RentalAvailabilityService $availability) {}

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q'));
        $categorySlug = (string) $request->query('kategori');
        $mode = (string) $request->query('mode', 'sewa'); // sewa | beli | semua
        $startDate = (string) $request->query('start_date');
        $endDate = (string) $request->query('end_date');

        $query = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->with(['category', 'variants', 'primaryImage']);

        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword) {
                $inner->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%')
                    ->orWhere('brand', 'like', '%'.$keyword.'%');
            });
        }

        if ($categorySlug !== '') {
            $query->whereHas('category', fn ($inner) => $inner->where('slug', $categorySlug));
        }

        if ($mode === 'sewa') {
            $query->rental();
        } elseif ($mode === 'beli') {
            $query->sale();
        }

        $dateNotice = null;
        $period = null;

        if ($startDate !== '' && $endDate !== '') {
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
                $dateNotice = $exception->getMessage();
            }
        }

        $products = $query->paginate(12)->withQueryString();

        return view('catalog.index', [
            'products' => $products,
            'categories' => Category::query()->active()->orderBy('sort_order')->get(),
            'keyword' => $keyword,
            'categorySlug' => $categorySlug,
            'mode' => $mode,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dateNotice' => $dateNotice,
            'period' => $period,
        ]);
    }
}
