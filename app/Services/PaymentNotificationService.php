<?php

namespace App\Services;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\WebhookProcessingStatus;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookEvent;
use App\Models\RentalOrder;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use App\Models\SalesPaymentAttempt;
use App\Support\MidtransSignature;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pemrosesan notifikasi webhook Midtrans untuk SEMUA domain (rental & retail).
 *
 * Satu endpoint menerima seluruh notifikasi merchant. Attempt diresolusi dari
 * `payment_attempts` (rental) lalu `sales_payment_attempts` (retail) berdasarkan
 * provider_order_id yang unik antar-tabel.
 *
 * Keamanan & konsistensi:
 * - signature SHA512 diverifikasi (MidtransSignature, dibagi bersama);
 * - amount & currency divalidasi terhadap attempt;
 * - deduplikasi global via event_hash unik pada payment_webhook_events;
 * - efek finansial idempotent & tidak menurunkan status final.
 */
class PaymentNotificationService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly SalesPaymentService $salesPayments,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Alur notifikasi webhook Midtrans untuk semua domain (rental & retail).
     *
     * Urutan wajib (lihat PHASE_8_SECURITY_AUDIT.md §Webhook):
     *   1. Validasi struktur payload.
     *   2. Verifikasi signature (SEBELUM event apa pun didaftarkan).
     *   3. Resolusi attempt.
     *   4. Validasi nominal & currency.
     *   5. Reservasi event (dedup + retry Failed).
     *   6. Terapkan efek finansial dalam transaksi yang terkunci.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status:string,reason?:string,event_id?:int}
     */
    public function handle(array $payload): array
    {
        $orderId = trim((string) ($payload['order_id'] ?? ''));
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $transactionId = (string) ($payload['transaction_id'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? '');
        $currency = (string) ($payload['currency'] ?? 'IDR');

        // 1. Validasi struktur. Payload cacat tidak boleh mengklaim fingerprint apa pun.
        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $transactionStatus === '') {
            $this->logger->log('payment.webhook_invalid_payload', null, 'Webhook ditolak: struktur payload tidak lengkap.', [
                'provider_order_id' => $orderId ?: null,
            ]);

            return ['status' => 'invalid_payload'];
        }

        // 2. Verifikasi signature SEBELUM event didaftarkan. Ini mencegah event
        //    poisoning: notifikasi dengan signature salah tidak boleh mengunci
        //    event_hash yang nantinya dipakai notifikasi valid.
        if (! MidtransSignature::isValid($orderId, $statusCode, $grossAmount, $signature)) {
            $this->auditInvalidSignature($payload, $orderId);

            return ['status' => 'invalid_signature'];
        }

        // 3. Resolusi attempt (rental lalu retail).
        $rentalAttempt = PaymentAttempt::query()->where('provider_order_id', $orderId)->first();
        $salesAttempt = $rentalAttempt === null
            ? SalesPaymentAttempt::query()->where('provider_order_id', $orderId)->first()
            : null;

        if ($rentalAttempt === null && $salesAttempt === null) {
            // Unknown attempt juga bisa terjadi karena race: attempt dibuat setelah
            // notifikasi datang. Jangan daftarkan event agar retry valid nantinya
            // tetap dapat diproses.
            $this->logger->log('payment.webhook_unknown_attempt', null, 'Webhook ditolak: attempt belum terdaftar.', [
                'provider_order_id' => $orderId,
            ]);

            return ['status' => 'unknown_attempt'];
        }

        $referenceAttempt = $rentalAttempt ?? $salesAttempt;

        // 4. Validasi nominal & currency terhadap attempt.
        if (Money::toInt($grossAmount) !== (int) $referenceAttempt->gross_amount) {
            $event = $this->registerTerminalRejection($payload, 'Nominal tidak cocok.');

            return ['status' => 'amount_mismatch', 'event_id' => $event->getKey()];
        }

        if (strtoupper($currency) !== strtoupper((string) $referenceAttempt->currency)) {
            $event = $this->registerTerminalRejection($payload, 'Mata uang tidak cocok.');

            return ['status' => 'currency_mismatch', 'event_id' => $event->getKey()];
        }

        // 5. Reservasi event: dedup untuk Processed/Duplicate/Received, retry aman
        //    untuk Failed. return null berarti tidak perlu diproses.
        $event = $this->reserveEvent($payload, $orderId, $transactionId, $transactionStatus);

        if ($event === null) {
            return ['status' => 'duplicate'];
        }

        $data = [
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'transaction_id' => $transactionId,
            'payment_type' => $paymentType,
            'gross_amount' => $grossAmount,
        ];

        // 6. Terapkan efek finansial di dalam transaksi terkunci (applyRental/applySales).
        try {
            $result = $rentalAttempt !== null
                ? $this->applyRental($rentalAttempt, $data)
                : $this->applySales($salesAttempt, $data);
        } catch (Throwable $exception) {
            // Gagal bersifat sementara: event disimpan dengan status Failed agar
            // dapat dicoba ulang oleh provider tanpa kehilangan audit trail.
            $event->forceFill([
                'processing_status' => WebhookProcessingStatus::Failed,
                'failure_reason' => mb_substr($exception->getMessage(), 0, 255),
                'processed_at' => now(),
            ])->save();

            report($exception);

            return ['status' => 'failed', 'reason' => $exception->getMessage(), 'event_id' => $event->getKey()];
        }

        $event->forceFill([
            'processing_status' => $result === 'duplicate_event'
                ? WebhookProcessingStatus::Duplicate
                : WebhookProcessingStatus::Processed,
            'failure_reason' => null,
            'processed_at' => now(),
        ])->save();

        return ['status' => $result, 'event_id' => $event->getKey()];
    }

    /**
     * Reservasi event untuk pemrosesan. Mengembalikan null bila event identik
     * sudah diproses, masih diproses (concurrent), atau memang ditolak terminal.
     *
     * @param  array<string, mixed>  $payload
     */
    private function reserveEvent(array $payload, string $orderId, string $transactionId, string $transactionStatus): ?PaymentWebhookEvent
    {
        $hash = MidtransSignature::eventHash($payload);

        $existing = PaymentWebhookEvent::query()->where('event_hash', $hash)->first();

        if ($existing !== null) {
            return DB::transaction(function () use ($existing) {
                $event = PaymentWebhookEvent::query()->whereKey($existing->getKey())->lockForUpdate()->firstOrFail();

                if ($event->processing_status !== WebhookProcessingStatus::Failed) {
                    return null;
                }

                // Failed boleh retry. Tandai Received di bawah row lock sehingga
                // retry paralel lain melihat event sedang diproses dan berhenti.
                $event->forceFill([
                    'processing_status' => WebhookProcessingStatus::Received,
                    'failure_reason' => null,
                    'processed_at' => null,
                ])->save();

                return $event;
            });
        }

        try {
            return PaymentWebhookEvent::query()->create([
                'event_hash' => $hash,
                'provider' => PaymentProvider::Midtrans,
                'provider_order_id' => $orderId ?: null,
                'provider_transaction_id' => $transactionId ?: null,
                'notification_status' => $transactionStatus ?: null,
                'processing_status' => WebhookProcessingStatus::Received,
                'payload_safe' => MidtransSignature::safePayload($payload),
                'received_at' => now(),
            ]);
        } catch (QueryException) {
            // Race concurrent: baris dibuat proses lain. Perlakukan sebagai duplikat.
            return null;
        }
    }

    /**
     * Catat penolakan signature pada namespace hash terpisah sehingga tidak pernah
     * mengklaim fingerprint notifikasi yang valid.
     *
     * @param  array<string, mixed>  $payload
     */
    private function auditInvalidSignature(array $payload, string $orderId): void
    {
        $hash = 'sig:'.hash('sha256', implode('|', [
            $orderId,
            (string) ($payload['status_code'] ?? ''),
            (string) ($payload['gross_amount'] ?? ''),
            (string) ($payload['signature_key'] ?? ''),
        ]));

        try {
            PaymentWebhookEvent::query()->firstOrCreate(
                ['event_hash' => $hash],
                [
                    'provider' => PaymentProvider::Midtrans,
                    'provider_order_id' => $orderId ?: null,
                    'provider_transaction_id' => (string) ($payload['transaction_id'] ?? '') ?: null,
                    'notification_status' => (string) ($payload['transaction_status'] ?? '') ?: null,
                    'processing_status' => WebhookProcessingStatus::Rejected,
                    'payload_safe' => MidtransSignature::safePayload($payload),
                    'failure_reason' => 'Signature tidak valid.',
                    'received_at' => now(),
                    'processed_at' => now(),
                ],
            );
        } catch (QueryException) {
            // Audit bersifat best-effort; keamanan tetap tidak tergantung pada ini.
        }

        $this->logger->log('payment.webhook_bad_signature', null, 'Webhook ditolak: signature tidak valid.', [
            'provider_order_id' => $orderId ?: null,
        ]);
    }

    /**
     * Catat penolakan terminal (nominal/currency) dengan signature valid.
     *
     * @param  array<string, mixed>  $payload
     */
    private function registerTerminalRejection(array $payload, string $reason): PaymentWebhookEvent
    {
        $hash = MidtransSignature::eventHash($payload);

        $event = PaymentWebhookEvent::query()->firstOrCreate(
            ['event_hash' => $hash],
            [
                'provider' => PaymentProvider::Midtrans,
                'provider_order_id' => (string) ($payload['order_id'] ?? '') ?: null,
                'provider_transaction_id' => (string) ($payload['transaction_id'] ?? '') ?: null,
                'notification_status' => (string) ($payload['transaction_status'] ?? '') ?: null,
                'processing_status' => WebhookProcessingStatus::Received,
                'payload_safe' => MidtransSignature::safePayload($payload),
                'received_at' => now(),
            ],
        );

        $event->forceFill([
            'processing_status' => WebhookProcessingStatus::Rejected,
            'failure_reason' => $reason,
            'processed_at' => now(),
        ])->save();

        return $event;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyRental(PaymentAttempt $attempt, array $data): string
    {
        return DB::transaction(function () use ($attempt, $data) {
            $attempt = PaymentAttempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->whereKey($attempt->payment_id)->lockForUpdate()->firstOrFail();
            $order = RentalOrder::query()->whereKey($attempt->rental_order_id)->lockForUpdate()->firstOrFail();

            $status = (string) $data['transaction_status'];
            $fraud = (string) $data['fraud_status'];

            if (in_array($payment->status, [PaymentStatus::Verified, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)
                && in_array($status, ['pending', 'deny', 'cancel', 'expire', 'failure'], true)) {
                $this->logger->log('payment.webhook_ignored', $payment, "Notifikasi '{$status}' diabaikan karena pembayaran sudah final.", ['provider_order_id' => $attempt->provider_order_id]);

                return 'ignored_final';
            }

            switch ($status) {
                case 'settlement':
                    return $this->settleRental($attempt, $payment, $order, $data, PaymentAttemptStatus::Settlement);
                case 'capture':
                    if ($fraud === 'challenge') {
                        $attempt->forceFill([
                            'status' => PaymentAttemptStatus::CaptureChallenged,
                            'fraud_status' => $fraud,
                            'provider_transaction_id' => $data['transaction_id'] ?: $attempt->provider_transaction_id,
                            'provider_payment_type' => $data['payment_type'] ?: $attempt->provider_payment_type,
                        ])->save();
                        $payment->forceFill(['status' => PaymentStatus::Processing])->save();

                        return 'capture_challenged';
                    }

                    return $this->settleRental($attempt, $payment, $order, $data, PaymentAttemptStatus::CaptureAccepted, $fraud);
                case 'pending':
                    $attempt->forceFill([
                        'status' => PaymentAttemptStatus::Pending,
                        'provider_transaction_id' => $data['transaction_id'] ?: $attempt->provider_transaction_id,
                        'provider_payment_type' => $data['payment_type'] ?: $attempt->provider_payment_type,
                    ])->save();

                    return 'pending';
                case 'deny':
                    return $this->failRental($attempt, $payment, $order, PaymentAttemptStatus::Denied, 'Pembayaran ditolak provider.', $data);
                case 'cancel':
                    return $this->failRental($attempt, $payment, $order, PaymentAttemptStatus::Cancelled, 'Transaksi dibatalkan.', $data, expire: true);
                case 'expire':
                    return $this->failRental($attempt, $payment, $order, PaymentAttemptStatus::Expired, 'Transaksi kedaluwarsa.', $data, expire: true, paymentStatus: PaymentStatus::Expired);
                case 'failure':
                    return $this->failRental($attempt, $payment, $order, PaymentAttemptStatus::Failed, 'Pembayaran gagal.', $data);
                case 'refund':
                    $attempt->forceFill(['status' => PaymentAttemptStatus::Refunded])->save();
                    $payment->forceFill(['status' => PaymentStatus::Refunded])->save();

                    return 'refunded';
                case 'partial_refund':
                    $attempt->forceFill(['status' => PaymentAttemptStatus::PartiallyRefunded])->save();
                    $payment->forceFill(['status' => PaymentStatus::PartiallyRefunded])->save();

                    return 'partially_refunded';
                case 'chargeback':
                case 'partial_chargeback':
                    $attempt->forceFill(['status' => PaymentAttemptStatus::Chargeback])->save();
                    $this->logger->log('payment.chargeback', $payment, "Chargeback diterima untuk {$attempt->provider_order_id}.", [], null);

                    return 'chargeback';
                default:
                    return 'noop';
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applySales(SalesPaymentAttempt $attempt, array $data): string
    {
        return DB::transaction(function () use ($attempt, $data) {
            $attempt = SalesPaymentAttempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
            $payment = SalesPayment::query()->whereKey($attempt->sales_payment_id)->lockForUpdate()->firstOrFail();
            $order = SalesOrder::query()->whereKey($payment->sales_order_id)->lockForUpdate()->firstOrFail();

            $status = (string) $data['transaction_status'];
            $fraud = (string) $data['fraud_status'];

            if (in_array($payment->status, [PaymentStatus::Verified, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)
                && in_array($status, ['pending', 'deny', 'cancel', 'expire', 'failure'], true)) {
                $this->logger->log('sales.webhook_ignored', $payment, "Notifikasi '{$status}' diabaikan karena pembayaran sudah final.", ['provider_order_id' => $attempt->provider_order_id]);

                return 'ignored_final';
            }

            switch ($status) {
                case 'settlement':
                    return $this->settleSales($attempt, $payment, $order, $data, PaymentAttemptStatus::Settlement);
                case 'capture':
                    if ($fraud === 'challenge') {
                        $attempt->forceFill([
                            'status' => PaymentAttemptStatus::CaptureChallenged,
                            'fraud_status' => $fraud,
                            'provider_transaction_id' => $data['transaction_id'] ?: $attempt->provider_transaction_id,
                            'provider_payment_type' => $data['payment_type'] ?: $attempt->provider_payment_type,
                        ])->save();
                        $payment->forceFill(['status' => PaymentStatus::Processing])->save();

                        return 'capture_challenged';
                    }

                    return $this->settleSales($attempt, $payment, $order, $data, PaymentAttemptStatus::CaptureAccepted, $fraud);
                case 'pending':
                    $attempt->forceFill([
                        'status' => PaymentAttemptStatus::Pending,
                        'provider_transaction_id' => $data['transaction_id'] ?: $attempt->provider_transaction_id,
                        'provider_payment_type' => $data['payment_type'] ?: $attempt->provider_payment_type,
                    ])->save();

                    return 'pending';
                case 'deny':
                    return $this->failSales($attempt, $payment, $order, PaymentAttemptStatus::Denied, 'Pembayaran ditolak provider.', $data);
                case 'cancel':
                    return $this->failSales($attempt, $payment, $order, PaymentAttemptStatus::Cancelled, 'Transaksi dibatalkan.', $data, expired: true);
                case 'expire':
                    return $this->failSales($attempt, $payment, $order, PaymentAttemptStatus::Expired, 'Transaksi kedaluwarsa.', $data, expired: true, paymentStatus: PaymentStatus::Expired);
                case 'failure':
                    return $this->failSales($attempt, $payment, $order, PaymentAttemptStatus::Failed, 'Pembayaran gagal.', $data);
                case 'refund':
                    $attempt->forceFill(['status' => PaymentAttemptStatus::Refunded])->save();
                    $payment->forceFill(['status' => PaymentStatus::Refunded])->save();

                    return 'refunded';
                case 'partial_refund':
                    $attempt->forceFill(['status' => PaymentAttemptStatus::PartiallyRefunded])->save();
                    $payment->forceFill(['status' => PaymentStatus::PartiallyRefunded])->save();

                    return 'partially_refunded';
                case 'chargeback':
                case 'partial_chargeback':
                    $attempt->forceFill(['status' => PaymentAttemptStatus::Chargeback])->save();
                    $this->logger->log('sales.chargeback', $payment, "Chargeback diterima untuk {$attempt->provider_order_id}.", [], null);

                    return 'chargeback';
                default:
                    return 'noop';
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function settleRental(PaymentAttempt $attempt, Payment $payment, RentalOrder $order, array $data, PaymentAttemptStatus $status, ?string $fraud = null): string
    {
        $attempt->forceFill([
            'status' => $status,
            'fraud_status' => $fraud,
            'provider_transaction_id' => $data['transaction_id'] ?: $attempt->provider_transaction_id,
            'provider_payment_type' => $data['payment_type'] ?: $attempt->provider_payment_type,
            'paid_at' => $attempt->paid_at ?? now(),
        ])->save();

        $this->payments->markSettled($payment, null, 'midtrans', 'Pembayaran gateway terverifikasi.', [
            'provider_order_id' => $attempt->provider_order_id,
            'transaction_id' => $data['transaction_id'],
        ]);

        return 'settled';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function settleSales(SalesPaymentAttempt $attempt, SalesPayment $payment, SalesOrder $order, array $data, PaymentAttemptStatus $status, ?string $fraud = null): string
    {
        $attempt->forceFill([
            'status' => $status,
            'fraud_status' => $fraud,
            'provider_transaction_id' => $data['transaction_id'] ?: $attempt->provider_transaction_id,
            'provider_payment_type' => $data['payment_type'] ?: $attempt->provider_payment_type,
            'paid_at' => $attempt->paid_at ?? now(),
        ])->save();

        $this->salesPayments->markSettled($payment, null, 'midtrans', 'Pembayaran gateway terverifikasi.', [
            'provider_order_id' => $attempt->provider_order_id,
            'transaction_id' => $data['transaction_id'],
        ]);

        return 'settled';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function failRental(PaymentAttempt $attempt, Payment $payment, RentalOrder $order, PaymentAttemptStatus $status, string $reason, array $data, bool $expire = false, PaymentStatus $paymentStatus = PaymentStatus::Failed): string
    {
        $attempt->forceFill([
            'status' => $status,
            'provider_transaction_id' => $data['transaction_id'] ?: $attempt->provider_transaction_id,
            'failed_at' => now(),
        ])->save();

        $payment->forceFill(['status' => $paymentStatus])->save();

        if (in_array($order->order_status, [RentalOrderStatus::PendingPayment, RentalOrderStatus::PaymentReview], true)) {
            $this->payments->cancelForFailure($order, $reason);
        }

        return $expire ? 'expired' : 'failed';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function failSales(SalesPaymentAttempt $attempt, SalesPayment $payment, SalesOrder $order, PaymentAttemptStatus $status, string $reason, array $data, bool $expired = false, PaymentStatus $paymentStatus = PaymentStatus::Failed): string
    {
        $attempt->forceFill([
            'status' => $status,
            'provider_transaction_id' => $data['transaction_id'] ?: $attempt->provider_transaction_id,
            'failed_at' => now(),
        ])->save();

        $payment->forceFill(['status' => $paymentStatus])->save();

        if (in_array($order->order_status, [SalesOrderStatus::PendingPayment, SalesOrderStatus::PaymentReview], true)) {
            $this->salesPayments->cancelForFailure($order, $reason, $expired);
        }

        return $expired ? 'expired' : 'failed';
    }

    private function reject(PaymentWebhookEvent $event, string $reason): void
    {
        $event->forceFill([
            'processing_status' => WebhookProcessingStatus::Rejected,
            'failure_reason' => $reason,
            'processed_at' => now(),
        ])->save();

        $this->logger->log('payment.webhook_rejected', null, "Webhook ditolak: {$reason}.", [
            'provider_order_id' => $event->provider_order_id,
        ]);
    }
}
