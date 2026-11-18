<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\RentalOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Pembuatan transaksi Snap Midtrans (Snap Redirect).
 *
 * Request HTTP ke provider tidak pernah dijalankan di dalam transaksi database
 * yang mengunci inventory (lihat §5.4 technical design).
 */
class MidtransPaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ActivityLogger $logger,
    ) {}

    public function startCheckout(Payment $payment, ?User $actor = null): PaymentAttempt
    {
        if ($payment->method !== PaymentMethod::Midtrans) {
            throw new RuntimeException('Transaksi Snap hanya untuk pembayaran metode Midtrans.');
        }

        // Fase 1: siapkan attempt di database (commit sebelum memanggil provider).
        [$attempt, $order] = DB::transaction(function () use ($payment, $actor) {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $order = RentalOrder::query()->whereKey($payment->rental_order_id)->lockForUpdate()->firstOrFail();

            if (in_array($order->order_status, [RentalOrderStatus::Cancelled, RentalOrderStatus::Completed], true)) {
                throw new RuntimeException('Order sudah tidak dapat dibayar.');
            }

            if (in_array($payment->status, [PaymentStatus::Verified, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
                throw new RuntimeException('Pembayaran sudah final.');
            }

            // Pakai ulang attempt aktif yang belum kedaluwarsa (mencegah double-click).
            $active = $payment->attempts()
                ->whereIn('status', [PaymentAttemptStatus::Pending->value, PaymentAttemptStatus::CaptureChallenged->value])
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest('id')
                ->first();

            if ($active !== null && filled($active->redirect_url)) {
                return [$active, $order];
            }

            $gross = $payment->amountRupiah();

            if ($gross <= 0) {
                throw new RuntimeException('Nominal pembayaran tidak valid.');
            }

            $attempt = $payment->attempts()->create([
                'rental_order_id' => $order->getKey(),
                'provider' => PaymentProvider::Midtrans,
                'provider_order_id' => $this->generateProviderOrderId($order, $payment),
                'status' => PaymentAttemptStatus::Pending,
                'gross_amount' => $gross,
                'currency' => 'IDR',
                'idempotency_key' => null,
                'expires_at' => CarbonImmutable::now()->addMinutes((int) config('gearora.midtrans.expiry_minutes', 60)),
            ]);

            $attempt->forceFill(['idempotency_key' => $attempt->provider_order_id])->save();

            $this->logger->log(
                'payment.attempt_created',
                $attempt,
                "Attempt pembayaran Midtrans untuk order {$order->order_code} dibuat ({$attempt->provider_order_id}).",
                ['provider_order_id' => $attempt->provider_order_id, 'gross_amount' => $gross],
                $actor,
            );

            return [$attempt, $order];
        });

        if (filled($attempt->redirect_url)) {
            return $attempt;
        }

        // Fase 2: panggil provider di luar transaction.
        try {
            $result = $this->gateway->createSnap($attempt, $order);

            $attempt->forceFill([
                'snap_token' => $result['token'],
                'redirect_url' => $result['redirect_url'],
                'provider_metadata' => array_merge((array) $attempt->provider_metadata, $result['metadata'] ?? []),
            ])->save();

            $this->logger->log(
                'payment.snap_created',
                $attempt,
                "Snap token untuk order {$order->order_code} berhasil dibuat.",
                ['provider_order_id' => $attempt->provider_order_id],
                $actor,
            );
        } catch (Throwable $exception) {
            $attempt->forceFill([
                'status' => PaymentAttemptStatus::Failed,
                'failed_at' => now(),
                'provider_metadata' => array_merge((array) $attempt->provider_metadata, ['error' => $exception->getMessage()]),
            ])->save();

            $this->logger->log(
                'payment.snap_failed',
                $attempt,
                "Gagal membuat Snap token untuk order {$order->order_code}.",
                ['provider_order_id' => $attempt->provider_order_id, 'error' => $exception->getMessage()],
                $actor,
            );

            throw $exception;
        }

        return $attempt->refresh();
    }

    private function generateProviderOrderId(RentalOrder $order, Payment $payment): string
    {
        $sequence = $payment->attempts()->count() + 1;

        for ($i = 0; $i < 5; $i++) {
            $id = sprintf('%s-A%02d', $order->order_code, $sequence + $i);

            if (! PaymentAttempt::query()->where('provider_order_id', $id)->exists()) {
                return $id;
            }
        }

        return $order->order_code.'-A'.strtoupper(bin2hex(random_bytes(3)));
    }
}
