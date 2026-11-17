<?php

namespace App\Http\Controllers\PublicSite;

use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\AvailabilityCheckRequest;
use App\Models\ProductVariant;
use App\Services\RentalAvailabilityService;
use App\Services\RentalPricingService;
use App\Support\RentalPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Ringkasan booking sebelum order dibuat. Draft disimpan di session agar pilihan
 * pelanggan tetap ada saat harus login terlebih dahulu.
 */
class BookingController extends Controller
{
    private const SESSION_KEY = 'rental_booking_draft';

    public function __construct(
        private readonly RentalAvailabilityService $availability,
        private readonly RentalPricingService $pricing,
    ) {}

    public function store(AvailabilityCheckRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['method'] = PaymentMethod::Transfer->value;
        $data['notes'] = null;

        if ($request->filled('method') && in_array($request->input('method'), PaymentMethod::values(), true)) {
            $data['method'] = (string) $request->input('method');
        }

        $request->session()->put(self::SESSION_KEY, $data);

        return redirect()->route('booking.summary');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $draft = $request->session()->get(self::SESSION_KEY);

        if (! is_array($draft)) {
            return redirect()->route('catalog.index')
                ->with('status', 'Pilih produk dan tanggal terlebih dahulu.');
        }

        $variant = ProductVariant::query()->with('product')->find($draft['variant_id']);

        if ($variant === null || $variant->product === null || $variant->product->status !== ProductStatus::Active) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('catalog.index')->with('status', 'Produk sudah tidak tersedia.');
        }

        $quantity = (int) $draft['quantity'];

        try {
            $period = RentalPeriod::from($draft['start_date'], $draft['end_date']);
            $period->assertValid(config('gearora.rental'));
            $result = $this->availability->check($variant, $quantity, $period);
            $quote = $this->pricing->quote([['variant' => $variant, 'quantity' => $quantity]], $period);
        } catch (Throwable $exception) {
            return redirect()->route('catalog.show', $variant->product)
                ->with('status', $exception->getMessage());
        }

        return view('rental.summary', [
            'variant' => $variant,
            'product' => $variant->product,
            'quantity' => $quantity,
            'method' => $draft['method'] ?? PaymentMethod::Transfer->value,
            'notes' => $draft['notes'] ?? null,
            'availability' => $result,
            'quote' => $quote,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('catalog.index')->with('status', 'Ringkasan booking dibatalkan.');
    }
}
