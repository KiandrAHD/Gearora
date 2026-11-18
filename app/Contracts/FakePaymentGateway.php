<?php

namespace App\Contracts;

use App\Models\PaymentAttempt;
use App\Models\SalesPaymentAttempt;
use RuntimeException;

/** Deterministic provider for tests and local development without credentials. */
class FakePaymentGateway implements PaymentGateway
{
    /** @var array<int, array<string, mixed>> */
    public array $created = [];

    public function createSnap(PaymentAttempt|SalesPaymentAttempt $attempt, PayableOrder $order): array
    {
        $token = 'fake-snap-'.$attempt->provider_order_id;
        $result = [
            'token' => $token,
            'redirect_url' => 'https://example.test/fake-snap/'.$token,
            'metadata' => ['fake' => true],
        ];
        $this->created[] = ['attempt_id' => $attempt->getKey(), 'order_code' => $order->payableCode()];

        return $result;
    }

    public function refund(PaymentAttempt|SalesPaymentAttempt $attempt, int $amount, string $refundKey): array
    {
        if (! $attempt->isSuccessful()) {
            throw new RuntimeException('Fake gateway: attempt belum settled.');
        }

        return ['status_code' => '200', 'refund_key' => $refundKey, 'refund_amount' => $amount];
    }

    public function cancel(PaymentAttempt|SalesPaymentAttempt $attempt): array
    {
        return ['status_code' => '200', 'order_id' => $attempt->provider_order_id];
    }
}
