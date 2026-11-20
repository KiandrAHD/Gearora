<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use App\Models\SalesPaymentAttempt;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Pembayaran retail (transfer manual, tunai, dan gateway Snap).
 *
 * Reuse infrastruktur gateway Phase 5 (PaymentGateway, signature, dedup webhook)
 * tanpa menyentuh domain Payment rental. Efek settlement terpusat di markSettled()
 * agar idempotent (webhook ganda tidak mengurangi stok dua kali).
 */
class SalesPaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly SalesOrderService $orders,
        private readonly SalesStockService $stock,
        private readonly ActivityLogger $logger,
    ) {}

    /** @return array<string, string|null> */
    public function instructions(): array
    {
        $config = config('gearora.payment.instructions');
        $setting = fn (string $key) => Setting::get($key, $config[$key] ?? null);

        return [
            'bank_name' => $setting('bank_name'),
            'bank_account_number' => $setting('bank_account_number'),
            'bank_account_name' => $setting('bank_account_name'),
            'cash_location' => $setting('cash_location'),
        ];
    }

    public function gatewayAvailable(): bool
    {
        return (bool) config('gearora.payment.gateway_enabled')
            && filled(config('gearora.midtrans.server_key'))
            && filled(config('gearora.midtrans.client_key'));
    }

    public function ensurePaymentFor(SalesOrder $order, PaymentMethod $method): SalesPayment
    {
        return $order->payments()->firstOrCreate(
            ['method' => $method],
            ['amount' => $order->totalRupiah(), 'status' => PaymentStatus::Unpaid],
        );
    }

    /** Buat transaksi Snap. Request HTTP provider dijalankan DI LUAR transaction. */
    public function startSnap(SalesPayment $payment, ?User $actor = null): SalesPaymentAttempt
    {
        if ($payment->method !== PaymentMethod::Midtrans) {
            throw new RuntimeException('Transaksi Snap hanya untuk pembayaran metode Midtrans.');
        }

        [$attempt, $order] = DB::transaction(function () use ($payment, $actor) {
            $payment = SalesPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $order = SalesOrder::query()->whereKey($payment->sales_order_id)->lockForUpdate()->firstOrFail();

            if (in_array($order->order_status, [SalesOrderStatus::Cancelled, SalesOrderStatus::Expired, SalesOrderStatus::Completed], true)) {
                throw new RuntimeException('Order sudah tidak dapat dibayar.');
            }

            if (in_array($payment->status, [PaymentStatus::Verified, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
                throw new RuntimeException('Pembayaran sudah final.');
            }

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
                'provider' => PaymentProvider::Midtrans,
                'provider_order_id' => $this->generateProviderOrderId($order, $payment),
                'status' => PaymentAttemptStatus::Pending,
                'gross_amount' => $gross,
                'currency' => 'IDR',
                'expires_at' => CarbonImmutable::now()->addMinutes((int) config('gearora.midtrans.expiry_minutes', 60)),
            ]);

            $attempt->forceFill(['idempotency_key' => $attempt->provider_order_id])->save();

            $this->logger->log(
                'sales.payment_attempt_created',
                $attempt,
                "Attempt pembayaran Midtrans untuk sales order {$order->order_code} dibuat ({$attempt->provider_order_id}).",
                ['provider_order_id' => $attempt->provider_order_id, 'gross_amount' => $gross],
                $actor,
            );

            return [$attempt, $order];
        });

        if (filled($attempt->redirect_url)) {
            return $attempt;
        }

        try {
            $result = $this->gateway->createSnap($attempt, $order);

            $attempt->forceFill([
                'snap_token' => $result['token'],
                'redirect_url' => $result['redirect_url'],
                'provider_metadata' => array_merge((array) $attempt->provider_metadata, $result['metadata'] ?? []),
            ])->save();

            $this->logger->log(
                'sales.snap_created',
                $attempt,
                "Snap token untuk sales order {$order->order_code} berhasil dibuat.",
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
                'sales.snap_failed',
                $attempt,
                "Gagal membuat Snap token untuk sales order {$order->order_code}.",
                ['error' => $exception->getMessage()],
                $actor,
            );

            throw $exception;
        }

        return $attempt->refresh();
    }

    public function submitProof(SalesPayment $payment, UploadedFile $file, ?string $reference = null, ?string $note = null): SalesPayment
    {
        if ($payment->method !== PaymentMethod::Transfer) {
            throw new RuntimeException('Bukti pembayaran hanya berlaku untuk metode transfer.');
        }

        if ($payment->status->isFinal()) {
            throw new RuntimeException('Pembayaran ini sudah final dan tidak dapat diubah.');
        }

        $disk = config('gearora.payment.proof_disk');
        $directory = 'sales-payment-proofs/'.$payment->sales_order_id;
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = Str::random(40).'.'.$extension;
        $path = $file->storeAs($directory, $filename, ['disk' => $disk]);

        return DB::transaction(function () use ($payment, $path, $reference, $note) {
            $payment = SalesPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $payment->forceFill([
                'proof_path' => $path,
                'reference' => $reference ?: $payment->reference,
                'customer_note' => $note,
                'status' => PaymentStatus::WaitingVerification,
                'proof_submitted_at' => now(),
                'verification_note' => null,
                'verified_by' => null,
                'verified_at' => null,
            ])->save();

            $order = $payment->order;

            if ($order->order_status === SalesOrderStatus::PendingPayment) {
                $this->orders->transition($order, SalesOrderStatus::PaymentReview, auth()->user(), 'Bukti pembayaran diunggah.');
            }

            $this->logger->log(
                'sales.payment_proof_submitted',
                $payment,
                "Bukti pembayaran sales order {$order->order_code} diunggah.",
                ['proof' => $path],
            );

            return $payment->refresh();
        });
    }

    public function verify(SalesPayment $payment, User $actor, ?string $note = null): SalesPayment
    {
        if ($payment->method->isGateway()) {
            throw new RuntimeException('Pembayaran gateway diverifikasi otomatis melalui webhook.');
        }

        if ($payment->status === PaymentStatus::Verified) {
            return $payment;
        }

        if (in_array($payment->status, [PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
            throw new RuntimeException('Pembayaran ini sudah final.');
        }

        if ($payment->method === PaymentMethod::Transfer && blank($payment->proof_path)) {
            throw new RuntimeException('Tidak dapat memverifikasi transfer tanpa bukti pembayaran.');
        }

        if ($this->amountMismatch($payment)) {
            throw new RuntimeException('Nominal pembayaran tidak sesuai dengan total tagihan.');
        }

        return $this->markSettled($payment, $actor, 'manual', $note);
    }

    public function recordCash(SalesPayment $payment, User $actor, ?string $note = null): SalesPayment
    {
        if ($payment->method !== PaymentMethod::Cash) {
            throw new RuntimeException('Pencatatan tunai hanya berlaku untuk metode tunai.');
        }

        if ($payment->status === PaymentStatus::Verified) {
            return $payment;
        }

        if ($this->amountMismatch($payment)) {
            throw new RuntimeException('Nominal pembayaran tidak sesuai dengan total tagihan.');
        }

        return $this->markSettled($payment, $actor, 'cash', $note ?? 'Pembayaran tunai diterima di lokasi.');
    }

    public function reject(SalesPayment $payment, User $actor, string $note): SalesPayment
    {
        if ($payment->status === PaymentStatus::Verified) {
            throw new RuntimeException('Pembayaran yang sudah terverifikasi tidak dapat ditolak.');
        }

        return DB::transaction(function () use ($payment, $actor, $note) {
            $payment = SalesPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $payment->forceFill([
                'status' => PaymentStatus::Rejected,
                'verification_note' => $note,
                'verified_by' => $actor->getKey(),
                'verified_at' => now(),
            ])->save();

            $order = $payment->order;

            if ($order->order_status === SalesOrderStatus::PaymentReview) {
                $this->orders->transition($order, SalesOrderStatus::PendingPayment, $actor, 'Bukti pembayaran ditolak.');
            }

            $this->logger->log('sales.payment_rejected', $payment, "Pembayaran sales order {$order->order_code} ditolak.", ['note' => $note], $actor);

            return $payment->refresh();
        });
    }

    /**
     * Terima pembayaran retail. Idempotent: bila sudah Verified, tidak ada efek
     * tambahan (mencegah stok dikurangi dua kali).
     *
     * @param  array<string, mixed>  $context
     */
    public function markSettled(SalesPayment $payment, ?User $actor, string $source, ?string $note = null, array $context = []): SalesPayment
    {
        return DB::transaction(function () use ($payment, $actor, $source, $note, $context) {
            $payment = SalesPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::Verified) {
                return $payment; // idempotent
            }

            if (in_array($payment->status, [PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
                throw new RuntimeException('Pembayaran yang sudah direfund tidak dapat diselesaikan ulang.');
            }

            $order = SalesOrder::query()->whereKey($payment->sales_order_id)->lockForUpdate()->firstOrFail();

            // Reservasi yang sudah kedaluwarsa/dilepas tidak boleh dikonsumsi lagi.
            // Bila order belum final tetapi tidak ada reservasi aktif, ini late
            // settlement: jangan konfirmasi order, jangan potong stok.
            $pendingObligation = in_array($order->order_status, [SalesOrderStatus::PendingPayment, SalesOrderStatus::PaymentReview], true);
            $activeReservations = $order->reservations()->active()->count();

            $late = in_array($order->order_status, [SalesOrderStatus::Cancelled, SalesOrderStatus::Expired], true)
                || in_array($payment->status, [PaymentStatus::Expired, PaymentStatus::Failed], true)
                || ($pendingObligation && $activeReservations === 0);

            $payment->forceFill([
                'status' => $late ? PaymentStatus::Processing : PaymentStatus::Verified,
                'verified_by' => $actor?->getKey(),
                'verified_at' => now(),
                'settled_at' => now(),
                'verification_note' => $late
                    ? 'Pembayaran diterima setelah order dibatalkan/kedaluwarsa; perlu rekonsiliasi/refund manual.'
                    : $note,
            ])->save();

            if ($late) {
                $this->logger->log(
                    'sales.payment_late_settlement',
                    $payment,
                    "Pembayaran sales order {$order->order_code} masuk setelah order final; tidak diproses otomatis.",
                    array_merge(['amount' => $payment->amountRupiah()], $context),
                    $actor,
                );

                return $payment->refresh();
            }

            // Kurangi stok fisik tepat sekali (konsumsi reservasi).
            $this->stock->consumeForOrder($order, $actor);

            $order->forceFill(['payment_status' => PaymentStatus::Verified])->save();
            $order->refresh();

            $this->confirmOrderAfterPayment($order, $actor);

            $this->logger->log(
                'sales.payment_verified',
                $payment,
                "Pembayaran sales order {$order->order_code} terverifikasi ({$source}).",
                array_merge(['amount' => $payment->amountRupiah(), 'source' => $source], $context),
                $actor,
            );

            return $payment->refresh();
        });
    }

    public function amountMismatch(SalesPayment $payment): bool
    {
        $total = $payment->order?->totalRupiah() ?? 0;

        return $total > 0 && $payment->amountRupiah() !== $total;
    }

    /** Batalkan order karena pembayaran gagal/kedaluwarsa; lepas reservasi stok. */
    public function cancelForFailure(SalesOrder $order, string $reason, bool $expired = false): void
    {
        $order->refresh();

        $target = $expired ? SalesOrderStatus::Expired : SalesOrderStatus::Cancelled;

        if (! in_array($order->order_status, [SalesOrderStatus::PendingPayment, SalesOrderStatus::PaymentReview], true)) {
            return;
        }

        if (! $order->order_status->canTransitionTo($target)) {
            return;
        }

        $this->orders->transition($order, $target, null, $reason);
    }

    private function confirmOrderAfterPayment(SalesOrder $order, ?User $actor): void
    {
        if ($order->order_status === SalesOrderStatus::PendingPayment) {
            $this->orders->transition($order, SalesOrderStatus::PaymentReview, $actor, 'Pembayaran terverifikasi.');
            $order->refresh();
        }

        if ($order->order_status === SalesOrderStatus::PaymentReview) {
            $this->orders->transition($order, SalesOrderStatus::Confirmed, $actor, 'Pembayaran terverifikasi.');
        }
    }

    private function generateProviderOrderId(SalesOrder $order, SalesPayment $payment): string
    {
        $sequence = $payment->attempts()->count() + 1;

        for ($i = 0; $i < 5; $i++) {
            $id = sprintf('%s-A%02d', $order->order_code, $sequence + $i);

            if (! SalesPaymentAttempt::query()->where('provider_order_id', $id)->exists()) {
                return $id;
            }
        }

        return $order->order_code.'-A'.strtoupper(bin2hex(random_bytes(3)));
    }
}
