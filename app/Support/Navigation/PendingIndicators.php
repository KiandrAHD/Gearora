<?php

namespace App\Support\Navigation;

use App\Enums\AdditionalChargeStatus;
use App\Enums\PaymentStatus;
use App\Enums\RentalAssetStatus;
use App\Enums\RentalOrderStatus;
use App\Models\AdditionalCharge;
use App\Models\Payment;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\User;

/**
 * Angka pending untuk badge sidebar.
 *
 * Dihitung sekali per request (memoized) dan hanya untuk item yang boleh
 * diakses pengguna, sehingga sidebar tidak menjalankan query berat berulang.
 */
final class PendingIndicators
{
    /** @var array<string, array<string, int>> */
    private static array $cache = [];

    /**
     * @return array<string, int>
     */
    public static function forUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $key = $user->getAuthIdentifier().'|'.($user->isOwner() ? 'owner' : 'staff');

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $counts = [];
        $today = now()->toDateString();

        if (AdminNavigation::allows($user, 'payments.view')) {
            $counts['payments_waiting'] = Payment::query()
                ->where('status', PaymentStatus::WaitingVerification->value)
                ->count();
        }

        if (AdminNavigation::allows($user, 'charges.view')) {
            $counts['charges_pending'] = AdditionalCharge::query()
                ->where('status', AdditionalChargeStatus::PendingApproval->value)
                ->count();
        }

        if (AdminNavigation::allows($user, 'inspections.view')) {
            $counts['inspections_pending'] = RentalAsset::query()
                ->where('status', RentalAssetStatus::InspectionPending->value)
                ->count();
        }

        if (AdminNavigation::allows($user, 'rentals.handover')) {
            $counts['pickups_today'] = RentalOrder::query()
                ->where('order_status', RentalOrderStatus::ReadyForPickup->value)
                ->whereDate('start_date', '<=', $today)
                ->count();
        }

        if (AdminNavigation::allows($user, 'rentals.return')) {
            $counts['returns_today'] = RentalOrder::query()
                ->where('order_status', RentalOrderStatus::PickedUp->value)
                ->whereDate('end_date', '<=', $today)
                ->count();
        }

        if (AdminNavigation::allows($user, 'rentals.view')) {
            $counts['overdue'] = RentalOrder::query()
                ->where('order_status', RentalOrderStatus::PickedUp->value)
                ->whereDate('end_date', '<', $today)
                ->count();
        }

        return self::$cache[$key] = $counts;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}
