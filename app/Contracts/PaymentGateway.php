<?php

namespace App\Contracts;

use App\Models\PaymentAttempt;
use App\Models\RentalOrder;

interface PaymentGateway
{
    /** @return array{token:string,redirect_url:string,metadata:array<string,mixed>} */
    public function createSnap(PaymentAttempt $attempt, RentalOrder $order): array;

    /** @return array<string,mixed> */
    public function refund(PaymentAttempt $attempt, int $amount, string $refundKey): array;

    /** @return array<string,mixed> */
    public function cancel(PaymentAttempt $attempt): array;
}
