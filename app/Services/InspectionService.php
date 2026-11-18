<?php

namespace App\Services;

use App\Enums\InspectionResult;
use App\Enums\InspectionStage;
use App\Enums\RentalAssetStatus;
use App\Models\Inspection;
use App\Models\InspectionPhoto;
use App\Models\RentalAsset;
use App\Models\RentalAssetAssignment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Inspeksi kondisi unit pada tahap handover (awal) dan return (akhir).
 * Menentukan status unit baru dan melepaskan assignment per unit saat inspeksi
 * return selesai (ADR-5.2).
 */
class InspectionService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handover(RentalAssetAssignment $assignment, array $data, User $actor): Inspection
    {
        return DB::transaction(function () use ($assignment, $data, $actor) {
            $assignment = RentalAssetAssignment::query()->whereKey($assignment->getKey())->lockForUpdate()->firstOrFail();
            $asset = RentalAsset::query()->whereKey($assignment->rental_asset_id)->lockForUpdate()->firstOrFail();

            $inspection = Inspection::query()->create([
                'rental_asset_id' => $asset->getKey(),
                'rental_order_id' => $assignment->rental_order_id,
                'rental_asset_assignment_id' => $assignment->getKey(),
                'stage' => InspectionStage::Handover,
                'condition_before' => $asset->condition->value,
                'condition_after' => $data['condition'] ?? $asset->condition->value,
                'completeness_status' => $data['completeness_status'] ?? 'complete',
                'damage_description' => $data['damage_description'] ?? null,
                'missing_items' => $data['missing_items'] ?? null,
                'result' => $data['result'] ?? InspectionResult::Pass->value,
                'inspected_by' => $actor->getKey(),
                'inspected_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->storePhotos($inspection, $data['photos'] ?? []);

            $this->logger->log('inspection.handover', $inspection, "Inspeksi handover unit {$asset->asset_code}.", [], $actor);

            return $inspection;
        });
    }

    /**
     * Inspeksi akhir + tentukan status unit + lepas assignment unit tersebut.
     *
     * @param  array<string, mixed>  $data
     */
    public function finalizeReturn(RentalAssetAssignment $assignment, array $data, User $actor): Inspection
    {
        $result = InspectionResult::from($data['result'] ?? InspectionResult::Pass->value);

        return DB::transaction(function () use ($assignment, $data, $actor, $result) {
            $assignment = RentalAssetAssignment::query()->whereKey($assignment->getKey())->lockForUpdate()->firstOrFail();
            $asset = RentalAsset::query()->whereKey($assignment->rental_asset_id)->lockForUpdate()->firstOrFail();

            if ($assignment->released_at !== null) {
                throw new RuntimeException('Unit ini sudah selesai diinspeksi.');
            }

            $inspection = Inspection::query()->create([
                'rental_asset_id' => $asset->getKey(),
                'rental_order_id' => $assignment->rental_order_id,
                'rental_asset_assignment_id' => $assignment->getKey(),
                'stage' => InspectionStage::Return,
                'condition_before' => $data['condition_before'] ?? $asset->condition->value,
                'condition_after' => $data['condition'] ?? $asset->condition->value,
                'completeness_status' => $data['completeness_status'] ?? 'complete',
                'damage_description' => $data['damage_description'] ?? null,
                'missing_items' => $data['missing_items'] ?? null,
                'result' => $result,
                'inspected_by' => $actor->getKey(),
                'inspected_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->storePhotos($inspection, $data['photos'] ?? []);

            // Status unit berikutnya.
            $nextStatus = $result === InspectionResult::Pass
                ? RentalAssetStatus::Available
                : $this->outcomeStatus($data['outcome_status'] ?? null);

            $asset->forceFill([
                'status' => $nextStatus,
                'condition' => $data['condition'] ?? $asset->condition,
                'notes' => $data['asset_notes'] ?? $asset->notes,
            ])->save();

            // Lepas assignment unit ini sekarang (bukan saat order returned).
            $assignment->forceFill([
                'status' => 'inspected',
                'current_inspection_id' => $inspection->getKey(),
                'released_at' => now(),
            ])->save();

            $this->logger->log(
                'inspection.return',
                $inspection,
                "Inspeksi return unit {$asset->asset_code}: {$result->value} → status {$nextStatus->value}.",
                ['result' => $result->value, 'asset_status' => $nextStatus->value],
                $actor,
            );

            return $inspection;
        });
    }

    /**
     * @param  array<int, mixed>  $files
     */
    private function storePhotos(Inspection $inspection, array $files): void
    {
        if ($files === []) {
            return;
        }

        $disk = config('gearora.payment.inspection_disk');
        $directory = 'inspection-photos/'.$inspection->rental_order_id;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $filename = Str::random(40).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $path = $file->storeAs($directory, $filename, ['disk' => $disk]);

            InspectionPhoto::query()->create([
                'inspection_id' => $inspection->getKey(),
                'path' => $path,
            ]);
        }
    }

    private function outcomeStatus(?string $status): RentalAssetStatus
    {
        $allowed = [
            RentalAssetStatus::Maintenance->value,
            RentalAssetStatus::Damaged->value,
            RentalAssetStatus::Lost->value,
        ];

        if ($status !== null && in_array($status, $allowed, true)) {
            return RentalAssetStatus::from($status);
        }

        return RentalAssetStatus::Maintenance;
    }
}
