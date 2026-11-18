<?php

namespace App\Contracts;

use App\Models\PaymentAttempt;
use App\Models\SalesPaymentAttempt;

interface PaymentGateway
{
    /** @return array{token:string,redirect_url:string,metadata:array<string,mixed>} */
    public function createSnap(PaymentAttempt|SalesPaymentAttempt $attempt, PayableOrder $order): array;

    /** @return array<string,mixed> */
    public function refund(PaymentAttempt|SalesPaymentAttempt $attempt, int $amount, string $refundKey): array;

    /** @return array<string,mixed> */
    public function cancel(PaymentAttempt|SalesPaymentAttempt $attempt): array;
}
