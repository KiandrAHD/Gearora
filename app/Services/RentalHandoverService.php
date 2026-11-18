<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RentalAssetStatus;
use App\Enums\RentalOrderStatus;
use App\Models\RentalAsset;
use App\Models\RentalHandover;
use App\Models\RentalHandoverItem;
use App\Models\RentalOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Serah terima unit (pickup). Semua langkah atomic: bila gagal menyimpan,
 * status order tidak berubah menjadi picked_up.
 */
class RentalHandoverService
{
    public function __construct(
        private readonly RentalOrderService $orders,
        private readonly InspectionService $inspections,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array{identity_type?:string,identity_checked?:bool,customer_acknowledged?:bool,notes?:string,asset_ids?:array<int,int>}  $data
     */
    public function handover(RentalOrder $order, array $data, User $actor): RentalHandover
    {
        if (! ($data['identity_checked'] ?? false)) {
            throw new RuntimeException('Identitas pelanggan wajib diperiksa sebelum serah terima.');
        }

        if (! ($data['customer_acknowledged'] ?? false)) {
            throw new RuntimeException('Pelanggan wajib menyetujui kondisi unit saat serah terima.');
        }

        $this->assertReady($order);

        return DB::transaction(function () use ($order, $data, $actor) {
            $order = RentalOrder::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->order_status !== RentalOrderStatus::ReadyForPickup) {
                throw new RuntimeException('Order tidak berada pada status siap diambil.');
            }

            $this->assertPaidAndDeposit($order);

            if ($order->handovers()->where('status', 'completed')->exists()) {
                throw new RuntimeException('Order ini sudah pernah diserahkan.');
            }

            $assignments = $order->assignments()
                ->whereNull('handed_over_at')
                ->whereNull('released_at')
                ->when(isset($data['asset_ids']) && $data['asset_ids'] !== [],
                    fn ($query) => $query->whereIn('id', $data['asset_ids']))
                ->lockForUpdate()
                ->get();

            if ($assignments->isEmpty()) {
                throw new RuntimeException('Tidak ada unit yang dapat diserahkan.');
            }

            $handover = RentalHandover::query()->create([
                'handover_code' => $this->code(),
                'rental_order_id' => $order->getKey(),
                'handed_over_at' => now(),
                'handled_by' => $actor->getKey(),
                'customer_identity_type' => $data['identity_type'] ?? null,
                'identity_checked' => true,
                'customer_acknowledged' => true,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($assignments as $assignment) {
                $asset = RentalAsset::query()->whereKey($assignment->rental_asset_id)->lockForUpdate()->firstOrFail();

                $item = RentalHandoverItem::query()->create([
                    'rental_handover_id' => $handover->getKey(),
                    'rental_asset_assignment_id' => $assignment->getKey(),
                    'rental_asset_id' => $asset->getKey(),
                    'asset_code_snapshot' => $asset->asset_code,
                    'accessories_checklist' => $data['accessories'][$asset->getKey()] ?? null,
                    'condition_notes' => $data['condition_notes'][$asset->getKey()] ?? null,
                    'customer_acknowledged' => true,
                ]);

                $assignment->forceFill([
                    'status' => 'handed_over',
                    'handed_over_at' => now(),
                    'rental_handover_item_id' => $item->getKey(),
                ])->save();

                $asset->forceFill(['status' => RentalAssetStatus::Rented])->save();

                $this->inspections->handover($assignment, [
                    'result' => 'pass',
                    'notes' => $data['condition_notes'][$asset->getKey()] ?? null,
                ], $actor);
            }

            $this->orders->transition($order, RentalOrderStatus::PickedUp, $actor, 'Unit diserahkan ke pelanggan.', viaWorkflow: true);

            $this->logger->log(
                'rental.handover',
                $handover,
                "Serah terima order {$order->order_code}: {$assignments->count()} unit.",
                ['handover_code' => $handover->handover_code, 'units' => $assignments->count()],
                $actor,
            );

            return $handover->refresh();
        });
    }

    private function assertReady(RentalOrder $order): void
    {
        $order->refresh();

        if ($order->order_status !== RentalOrderStatus::ReadyForPickup) {
            throw new RuntimeException('Order belum siap diambil.');
        }

        $this->assertPaidAndDeposit($order);
    }

    private function assertPaidAndDeposit(RentalOrder $order): void
    {
        if (! $order->payments()->where('status', PaymentStatus::Verified->value)->exists()) {
            throw new RuntimeException('Pembayaran belum terverifikasi; unit tidak dapat diserahkan.');
        }

        $deposit = $order->deposit;

        if ($deposit !== null && (int) $deposit->required_amount > 0 && (int) $deposit->collected_amount < (int) $deposit->required_amount) {
            throw new RuntimeException('Deposit belum diterima sepenuhnya; unit tidak dapat diserahkan.');
        }
    }

    private function code(): string
    {
        return 'HOV-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }
}
