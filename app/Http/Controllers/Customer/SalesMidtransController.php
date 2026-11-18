<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Services\SalesPaymentService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Throwable;

class SalesMidtransController extends Controller
{
    public function __construct(private readonly SalesPaymentService $payments) {}

    public function start(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('view', $salesOrder);

        if (! $this->payments->gatewayAvailable()) {
            return back()->with('error', 'Pembayaran otomatis belum tersedia. Gunakan transfer bank atau tunai.');
        }

        if (in_array($salesOrder->order_status, [SalesOrderStatus::Cancelled, SalesOrderStatus::Expired, SalesOrderStatus::Completed], true)) {
            return back()->with('error', 'Pesanan ini tidak dapat dibayar.');
        }

        $payment = $this->payments->ensurePaymentFor($salesOrder, PaymentMethod::Midtrans);

        if (in_array($payment->status, [PaymentStatus::Verified, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
            return back()->with('status', 'Pembayaran sudah diterima.');
        }

        try {
            $attempt = $this->payments->startSnap($payment, request()->user());
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
}
