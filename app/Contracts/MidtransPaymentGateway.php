<?php

namespace App\Contracts;

use App\Models\PaymentAttempt;
use App\Models\RentalOrder;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransPaymentGateway implements PaymentGateway
{
    public function __construct()
    {
        Config::$serverKey = (string) config('gearora.midtrans.server_key');
        Config::$clientKey = (string) config('gearora.midtrans.client_key');
        Config::$isProduction = (bool) config('gearora.midtrans.is_production');
        Config::$paymentIdempotencyKey = null;
    }

    public function createSnap(PaymentAttempt $attempt, RentalOrder $order): array
    {
        $result = Snap::createTransaction($this->params($attempt, $order));

        return [
            'token' => (string) $result->token,
            'redirect_url' => (string) $result->redirect_url,
            'metadata' => [
                'status_code' => $result->status_code ?? null,
            ],
        ];
    }

    public function refund(PaymentAttempt $attempt, int $amount, string $refundKey): array
    {
        $result = Transaction::refund($attempt->provider_order_id, [
            'refund_key' => $refundKey,
            'amount' => $amount,
            'reason' => 'GEARORA rental refund',
        ]);

        return is_object($result) ? (array) $result : (array) $result;
    }

    public function cancel(PaymentAttempt $attempt): array
    {
        $result = Transaction::cancel($attempt->provider_order_id);

        return is_object($result) ? (array) $result : (array) $result;
    }

    /** @return array<string,mixed> */
    private function params(PaymentAttempt $attempt, RentalOrder $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                'id' => 'rental-'.$item->id,
                'price' => (int) round((float) $item->rental_price_snapshot),
                'quantity' => (int) $item->quantity * (int) $item->duration_days,
                'name' => mb_substr($item->product_name_snapshot.' - '.$item->variant_name_snapshot, 0, 50),
            ];
        }

        if ((int) $order->deposit_total > 0) {
            $items[] = [
                'id' => 'deposit-'.$order->id,
                'price' => (int) round((float) $order->deposit_total),
                'quantity' => 1,
                'name' => 'Deposit jaminan rental',
            ];
        }

        return [
            'transaction_details' => [
                'order_id' => $attempt->provider_order_id,
                'gross_amount' => $attempt->gross_amount,
            ],
            'customer_details' => [
                'first_name' => mb_substr($order->customer->name, 0, 50),
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
            ],
            'item_details' => $items,
            'expiry' => [
                'unit' => 'minute',
                'duration' => (int) config('gearora.midtrans.expiry_minutes', 60),
            ],
        ];
    }
}
