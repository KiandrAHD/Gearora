<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\TripPackage;
use App\Models\User;

/**
 * Paket trip adalah agregat lintas domain (rental + retail). Staf yang boleh
 * melihat salah satu domain boleh melihat paket; pelanggan hanya paket miliknya.
 */
class TripPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner()
            || $user->hasAnyPermission([
                PermissionName::RentalsView->value,
                PermissionName::SalesView->value,
            ]);
    }

    public function view(User $user, TripPackage $package): bool
    {
        if ($package->customer_id === $user->getKey()) {
            return true;
        }

        return $this->viewAny($user);
    }
}
