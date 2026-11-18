<?php

namespace App\Services;

use App\Enums\RentalAssetStatus;
use App\Enums\RentalOrderStatus;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\RentalReturn;
use App\Models\RentalReturnItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Penerimaan unit kembali (return). Mendukung partial return: unit yang kembali
 * dicatat per unit; order baru menjadi `returned` bila seluruh unit sudah kembali.
 */
class RentalReturnService
{
    public function __construct(
        private readonly RentalOrderService $orders,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array<int, int>  $assetIds  ID assignment yang dikembalikan; kosong = semua sisa.
     * @param  array{notes?:string,customer_acknowledged?:bool}  $data
     */
    public function receive(RentalOrder $order, array $assetIds, array $data, User $actor): RentalReturn
    {
        return DB::transaction(function () use ($order, $assetIds, $data, $actor) {
            $order = RentalOrder::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->order_status !== RentalOrderStatus::PickedUp) {
                throw new RuntimeException('Hanya order yang sedang disewa yang dapat dikembalikan.');
            }

            $assignments = $order->assignments()
                ->whereNotNull('handed_over_at')
                ->whereNull('returned_at')
                ->when($assetIds !== [], fn ($query) => $query->whereIn('id', $assetIds))
                ->lockForUpdate()
                ->get();

            if ($assignments->isEmpty()) {
                throw new RuntimeException('Tidak ada unit yang dapat diterima kembali.');
            }

            $return = RentalReturn::query()->create([
                'return_code' => $this->code(),
                'rental_order_id' => $order->getKey(),
                'returned_at' => now(),
                'received_by' => $actor->getKey(),
                'customer_acknowledged' => (bool) ($data['customer_acknowledged'] ?? false),
                'status' => 'completed',
                'general_notes' => $data['notes'] ?? null,
            ]);

            foreach ($assignments as $assignment) {
                $asset = RentalAsset::query()->whereKey($assignment->rental_asset_id)->lockForUpdate()->firstOrFail();

                $item = RentalReturnItem::query()->create([
                    'rental_return_id' => $return->getKey(),
                    'rental_asset_assignment_id' => $assignment->getKey(),
                    'rental_asset_id' => $asset->getKey(),
                    'asset_code_snapshot' => $asset->asset_code,
                    'condition_notes' => $data['condition_notes'][$asset->getKey()] ?? null,
                    'customer_acknowledged' => (bool) ($data['customer_acknowledged'] ?? false),
                ]);

                $assignment->forceFill([
                    'status' => 'returned',
                    'returned_at' => now(),
                    'rental_return_item_id' => $item->getKey(),
                ])->save();

                // Belum boleh available sampai inspeksi selesai.
                $asset->forceFill(['status' => RentalAssetStatus::InspectionPending])->save();
            }

            $this->logger->log(
                'rental.return_received',
                $return,
                "Penerimaan kembali order {$order->order_code}: {$assignments->count()} unit menunggu inspeksi.",
                ['return_code' => $return->return_code, 'units' => $assignments->count()],
                $actor,
            );

            $this->completeIfAllReturned($order, $actor);

            return $return->refresh();
        });
    }

    /** Pindahkan order ke `returned` bila seluruh unit sudah kembali. */
    public function completeIfAllReturned(RentalOrder $order, ?User $actor = null): void
    {
        $order->refresh();

        $pending = $order->assignments()->whereNull('returned_at')->count();

        if ($pending > 0) {
            return;
        }

        if ($order->order_status === RentalOrderStatus::PickedUp) {
            $this->orders->transition($order, RentalOrderStatus::Returned, $actor, 'Seluruh unit telah dikembalikan.', viaWorkflow: true);
        }
    }

    /** Selesaikan order bila semua unit sudah diinspeksi & dilepas serta kewajiban selesai. */
    public function tryComplete(RentalOrder $order, User $actor): bool
    {
        $order->refresh();

        if ($order->order_status !== RentalOrderStatus::Returned) {
            return false;
        }

        if ($order->assignments()->whereNull('released_at')->exists()) {
            return false;
        }

        if ($order->additionalCharges()->whereIn('status', ['pending_approval', 'approved', 'partially_settled'])->exists()) {
            return false;
        }

        $deposit = $order->deposit;

        if ($deposit !== null && $deposit->balance() > 0) {
            return false;
        }

        $this->orders->transition($order, RentalOrderStatus::Completed, $actor, 'Inspeksi & deposit selesai.', viaWorkflow: true);

        return true;
    }

    private function code(): string
    {
        return 'RTN-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }
}
