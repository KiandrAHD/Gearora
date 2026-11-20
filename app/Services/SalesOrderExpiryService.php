<?php

namespace App\Services;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\SalesReservationStatus;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use App\Models\SalesPaymentAttempt;
use App\Models\SalesStockReservation;
use Illuminate\Support\Facades\DB;

/**
 * Kedaluwarsa retail payment/reservation tanpa bergantung pada cron.
 *
 * Aman dipanggil dari command dan jalur aplikasi. Semua mutasi dilakukan di
 * bawah lock order agar settlement webhook dan expiry tidak dapat mengonsumsi
 * stok yang sama.
 */
class SalesOrderExpiryService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /** @return int jumlah sales order yang benar-benar kedaluwarsa */
    public function expireStale(int $limit = 50): int
    {
        $ids = SalesOrder::query()
            ->whereIn('order_status', [
                SalesOrderStatus::PendingPayment->value,
                SalesOrderStatus::PaymentReview->value,
            ])
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            if ($this->expireOrder((int) $id)) {
                $expired++;
            }
        }

        return $expired;
    }

    /**
     * @return bool true bila order dialihkan ke Expired pada pemanggilan ini
     */
    public function expireOrder(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $order = SalesOrder::query()->whereKey($orderId)->lockForUpdate()->first();

            if ($order === null
                || ! in_array($order->order_status, [SalesOrderStatus::PendingPayment, SalesOrderStatus::PaymentReview], true)
                || $order->expires_at === null
                || $order->expires_at->isAfter(now())) {
                return false;
            }

            $payments = SalesPayment::query()
                ->where('sales_order_id', $order->getKey())
                ->lockForUpdate()
                ->get();

            $paymentIds = $payments->pluck('id');
            $successfulAttempt = SalesPaymentAttempt::query()
                ->whereIn('sales_payment_id', $paymentIds)
                ->whereIn('status', [
                    PaymentAttemptStatus::Settlement->value,
                    PaymentAttemptStatus::CaptureAccepted->value,
                ])
                ->lockForUpdate()
                ->exists();

            // Settlement sudah dimulai/selesai. Jangan lepas stok; jalur payment
            // akan menentukan hasil final secara idempoten.
            if ($successfulAttempt || $payments->contains(fn (SalesPayment $payment) => $payment->status === PaymentStatus::Verified)) {
                return false;
            }

            SalesPaymentAttempt::query()
                ->whereIn('sales_payment_id', $paymentIds)
                ->whereIn('status', [
                    PaymentAttemptStatus::Pending->value,
                    PaymentAttemptStatus::CaptureChallenged->value,
                ])
                ->lockForUpdate()
                ->update([
                    'status' => PaymentAttemptStatus::Expired->value,
                    'failed_at' => now(),
                    'updated_at' => now(),
                ]);

            foreach ($payments as $payment) {
                if (! $payment->status->isFinal()) {
                    $payment->forceFill([
                        'status' => PaymentStatus::Expired,
                        'verification_note' => 'Pembayaran kedaluwarsa sebelum settlement.',
                    ])->save();
                }
            }

            // Jangan memakai scopeActive(): reservation ini memang sudah expired
            // secara waktu namun statusnya belum dimutasi hingga titik atomik ini.
            SalesStockReservation::query()
                ->where('sales_order_id', $order->getKey())
                ->where('status', SalesReservationStatus::Reserved->value)
                ->lockForUpdate()
                ->update([
                    'status' => SalesReservationStatus::Expired->value,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

            $order->forceFill([
                'order_status' => SalesOrderStatus::Expired,
                'payment_status' => PaymentStatus::Expired,
                'cancellation_reason' => 'Reservasi stok dan pembayaran kedaluwarsa.',
            ])->save();

            $this->logger->log(
                'sales_order.expired',
                $order,
                "Sales order {$order->order_code} kedaluwarsa; reservasi stok dilepas.",
                ['order_code' => $order->order_code],
            );

            return true;
        });
    }
}
