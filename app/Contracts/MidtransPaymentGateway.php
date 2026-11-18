<?php

namespace App\Contracts;

use App\Models\PaymentAttempt;
use App\Models\SalesPaymentAttempt;
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

    public function createSnap(PaymentAttempt|SalesPaymentAttempt $attempt, PayableOrder $order): array
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

    public function refund(PaymentAttempt|SalesPaymentAttempt $attempt, int $amount, string $refundKey): array
    {
        $result = Transaction::refund($attempt->provider_order_id, [
            'refund_key' => $refundKey,
            'amount' => $amount,
            'reason' => 'GEARORA refund',
        ]);

        return (array) $result;
    }

    public function cancel(PaymentAttempt|SalesPaymentAttempt $attempt): array
    {
        $result = Transaction::cancel($attempt->provider_order_id);

        return (array) $result;
    }

    /** @return array<string,mixed> */
    private function params(PaymentAttempt|SalesPaymentAttempt $attempt, PayableOrder $order): array
    {
        $customer = $order->payableCustomer();

        return [
            'transaction_details' => [
                'order_id' => $attempt->provider_order_id,
                'gross_amount' => $attempt->gross_amount,
            ],
            'customer_details' => [
                'first_name' => mb_substr((string) $customer?->name, 0, 50),
                'email' => $customer?->email,
                'phone' => $customer?->phone,
            ],
            'item_details' => $order->payableItemDetails(),
            'expiry' => [
                'unit' => 'minute',
                'duration' => (int) config('gearora.midtrans.expiry_minutes', 60),
            ],
        ];
    }
}
