<?php

namespace App\Services;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\DB;

/**
 * Pemeriksaan opportunistik untuk transaksi yang kedaluwarsa.
 *
 * Hosting target tidak menjamin cron/worker, sehingga pemeriksaan ini dipanggil
 * saat dashboard/availability diakses, dan juga tersedia lewat Artisan command.
 */
class PaymentExpiryService
{
    public function __construct(private readonly PaymentService $payments) {}

    /** @return int jumlah order yang dibatalkan karena kedaluwarsa */
    public function expireStale(int $limit = 50): int
    {
        $attempts = PaymentAttempt::query()
            ->where('status', PaymentAttemptStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->latest('id')
            ->limit($limit)
            ->get();

        $cancelled = 0;

        foreach ($attempts as $attempt) {
            $hasSuccess = PaymentAttempt::query()
                ->where('payment_id', $attempt->payment_id)
                ->whereIn('status', [
                    PaymentAttemptStatus::Settlement->value,
                    PaymentAttemptStatus::CaptureAccepted->value,
                ])
                ->exists();

            if ($hasSuccess) {
                continue;
            }

            DB::transaction(function () use ($attempt, &$cancelled) {
                $attempt->forceFill([
                    'status' => PaymentAttemptStatus::Expired,
                    'failed_at' => now(),
                ])->save();

                $payment = Payment::query()->whereKey($attempt->payment_id)->lockForUpdate()->first();

                if ($payment !== null && ! $payment->status->isFinal()) {
                    $payment->forceFill(['status' => PaymentStatus::Expired])->save();
                }

                if ($payment !== null) {
                    $order = $payment->order;

                    if ($order !== null && in_array($order->order_status->value, ['pending_payment', 'payment_review'], true)) {
                        $this->payments->cancelForFailure($order, 'Transaksi pembayaran kedaluwarsa.');
                        $cancelled++;
                    }
                }
            });
        }

        return $cancelled;
    }
}
