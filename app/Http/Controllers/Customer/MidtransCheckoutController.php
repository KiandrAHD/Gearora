<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
use App\Services\MidtransPaymentService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class MidtransCheckoutController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly MidtransPaymentService $midtrans,
    ) {}

    public function start(Request $request, RentalOrder $order): RedirectResponse
    {
        $this->authorize('view', $order);

        if (! $this->payments->gatewayAvailable()) {
            return back()->with('error', 'Pembayaran otomatis belum tersedia. Gunakan transfer bank atau hubungi admin.');
        }

        if (in_array($order->order_status, [RentalOrderStatus::Cancelled, RentalOrderStatus::Completed], true)) {
            return back()->with('error', 'Order ini tidak dapat dibayar.');
        }

        $payment = $this->payments->ensurePaymentFor($order, PaymentMethod::Midtrans);

        if (in_array($payment->status, [PaymentStatus::Verified, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
            return back()->with('status', 'Pembayaran sudah diterima.');
        }

        try {
            $attempt = $this->midtrans->startCheckout($payment, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal memulai pembayaran otomatis. Silakan coba lagi.');
        }

        if (blank($attempt->redirect_url)) {
            return back()->with('error', 'Tautan pembayaran tidak tersedia. Silakan coba lagi.');
        }

        return redirect()->away($attempt->redirect_url);
    }

    /** Halaman kembali setelah menyelesaikan pembayaran. Status tetap dari database/webhook. */
    public function finish(Request $request, RentalOrder $order): RedirectResponse
    {
        $this->authorize('view', $order);

        return redirect()->route('customer.orders.show', $order)
            ->with('status', 'Pembayaran diproses. Status akan diperbarui otomatis setelah konfirmasi diterima.');
    }
}
