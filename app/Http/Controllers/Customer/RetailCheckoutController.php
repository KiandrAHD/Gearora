<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\RetailCheckoutRequest;
use App\Services\SalesCartService;
use App\Services\SalesOrderService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RetailCheckoutController extends Controller
{
    public function __construct(
        private readonly SalesCartService $cart,
        private readonly SalesOrderService $orders,
    ) {}

    public function store(RetailCheckoutRequest $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('retail.cart.index')->with('error', 'Keranjang kosong.');
        }

        $method = PaymentMethod::from($request->validated('payment_method'));

        try {
            $order = $this->orders->checkout(
                $request->user(),
                $this->cart->checkoutItems(),
                $method,
                $request->validated('notes'),
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->cart->clear();

        return redirect()->route('customer.sales.show', $order)
            ->with('status', "Pesanan retail {$order->order_code} dibuat. Selesaikan pembayaran.");
    }
}
