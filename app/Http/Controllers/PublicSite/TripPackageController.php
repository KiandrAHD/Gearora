<?php

namespace App\Http\Controllers\PublicSite;

use App\Data\TripPackageData;
use App\Data\TripPlanData;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PackageCheckoutRequest;
use App\Http\Requests\PublicSite\TripPackageRequest;
use App\Models\ProductVariant;
use App\Models\TripPackage;
use App\Services\RentalPricingService;
use App\Services\TripPackageCheckoutService;
use App\Support\Money;
use App\Support\RentalPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Draf paket Trip Planner (session) dan ringkasan pratinjau sebelum checkout.
 * Draft hanya menyimpan pilihan; seluruh harga & ketersediaan dihitung ulang
 * server-side saat pratinjau dan saat checkout.
 */
class TripPackageController extends Controller
{
    private const SESSION_KEY = 'trip_package_draft';

    public function __construct(
        private readonly RentalPricingService $pricing,
        private readonly TripPackageCheckoutService $checkout,
    ) {}

    public function store(TripPackageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['notes'] = filled($data['notes'] ?? null) ? (string) $data['notes'] : null;

        $request->session()->put(self::SESSION_KEY, $data);

        return redirect()->route('trip-planner.package.review');
    }

    public function review(Request $request): View|RedirectResponse
    {
        $draft = $request->session()->get(self::SESSION_KEY);

        if (! is_array($draft)) {
            return redirect()->route('trip-planner.index')
                ->with('status', 'Isi rencana trip dan pilih gear terlebih dahulu.');
        }

        $preview = $this->preview($draft);

        return view('trip-planner.package-review', [
            'plan' => $preview['plan'],
            'rentalLines' => $preview['rental_lines'],
            'saleLines' => $preview['sale_lines'],
            'rentalQuote' => $preview['rental_quote'],
            'rentalTotal' => $preview['rental_total'],
            'saleTotal' => $preview['sale_total'],
            'grandTotal' => $preview['grand_total'],
            'notice' => $preview['notice'],
            'notes' => $draft['notes'] ?? null,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('trip-planner.index')->with('status', 'Pratinjau paket dibatalkan.');
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function preview(array $draft): array
    {
        $plan = TripPlanData::fromArray($draft);
        $items = array_values((array) ($draft['items'] ?? []));

        $rentalItems = array_values(array_filter($items, fn ($i) => ($i['fulfillment'] ?? null) === 'sewa'));
        $saleItems = array_values(array_filter($items, fn ($i) => ($i['fulfillment'] ?? null) === 'beli'));

        $notice = null;
        $rentalQuote = null;
        $rentalTotal = 0;

        if ($rentalItems !== []) {
            if ($plan->startDate === null || $plan->endDate === null) {
                $notice = 'Isi tanggal mulai & kembali bila ingin menyertakan gear sewa.';
            } else {
                try {
                    $period = RentalPeriod::from($plan->startDate, $plan->endDate);
                    $period->assertValid(config('gearora.rental'));

                    $lines = [];
                    foreach ($rentalItems as $item) {
                        $variant = ProductVariant::query()->with('product')->find((int) $item['variant_id']);
                        if ($variant !== null) {
                            $lines[] = ['variant' => $variant, 'quantity' => (int) $item['quantity']];
                        }
                    }

                    if ($lines !== []) {
                        $rentalQuote = $this->pricing->quote($lines, $period);
                        $rentalTotal = Money::toInt($rentalQuote->total);
                    }
                } catch (Throwable $exception) {
                    $notice = $exception->getMessage();
                }
            }
        }

        $saleLines = [];
        $saleTotal = 0;

        foreach ($saleItems as $item) {
            $variant = ProductVariant::query()->with('product')->find((int) $item['variant_id']);

            if ($variant === null) {
                continue;
            }

            $unit = Money::toInt($variant->effectiveSalePrice());
            $quantity = (int) $item['quantity'];
            $lineTotal = $unit * $quantity;
            $saleTotal += $lineTotal;

            $saleLines[] = ['variant' => $variant, 'quantity' => $quantity, 'unit_price' => $unit, 'line_total' => $lineTotal];
        }

        $rentalLines = $rentalQuote?->lines ?? [];

        return [
            'plan' => $plan,
            'rental_lines' => $rentalLines,
            'sale_lines' => $saleLines,
            'rental_quote' => $rentalQuote,
            'rental_total' => $rentalTotal,
            'sale_total' => $saleTotal,
            'grand_total' => $rentalTotal + $saleTotal,
            'notice' => $notice,
        ];
    }

    public function checkout(PackageCheckoutRequest $request): RedirectResponse
    {
        $draft = $request->session()->get(self::SESSION_KEY);

        if (! is_array($draft)) {
            return redirect()->route('trip-planner.index')
                ->with('error', 'Pratinjau paket sudah kedaluwarsa. Ulangi pemilihan gear.');
        }

        $plan = TripPlanData::fromArray($draft);
        $packageData = new TripPackageData(
            plan: $plan,
            items: array_values((array) ($draft['items'] ?? [])),
            method: PaymentMethod::from($request->validated('payment_method')),
            notes: $request->validated('notes') ?? ($draft['notes'] ?? null),
        );

        try {
            $package = $this->checkout->checkout($request->user(), $packageData);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('customer.packages.show', $package)
            ->with('status', "Paket trip {$package->code} dibuat. Selesaikan pembayaran tiap pesanan.");
    }

    public function show(Request $request, TripPackage $package): View
    {
        $this->authorizePackage($request, $package);

        $package->load(['rentalOrder.payments', 'rentalOrder.items', 'salesOrder.payments', 'salesOrder.items']);

        return view('trip-planner.package-show', ['package' => $package]);
    }

    public function document(Request $request, TripPackage $package): View
    {
        $this->authorizePackage($request, $package);

        $package->load(['rentalOrder.items', 'salesOrder.items', 'customer']);

        return view('trip-planner.package-document', ['package' => $package]);
    }

    public function pdf(Request $request, TripPackage $package): Response
    {
        $this->authorizePackage($request, $package);

        $package->load(['rentalOrder.items', 'salesOrder.items', 'customer']);

        return Pdf::loadView('trip-planner.package-pdf', ['package' => $package])
            ->setPaper('a4')
            ->download('paket-'.$package->code.'.pdf');
    }

    private function authorizePackage(Request $request, TripPackage $package): void
    {
        abort_unless(
            $request->user() !== null && $package->customer_id === $request->user()->getKey(),
            403,
        );
    }
}
