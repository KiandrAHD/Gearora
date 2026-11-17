<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\RentalOrder;
use App\Models\User;

class RentalOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::RentalsView->value);
    }

    public function view(User $user, RentalOrder $order): bool
    {
        // Customer hanya dapat membaca order miliknya sendiri.
        if ($user->getKey() === $order->customer_id) {
            return true;
        }

        return $user->hasPermission(PermissionName::RentalsView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::RentalsCreate->value);
    }

    public function update(User $user, RentalOrder $order): bool
    {
        return $user->hasPermission(PermissionName::RentalsUpdate->value);
    }

    public function cancel(User $user, RentalOrder $order): bool
    {
        return $user->hasPermission(PermissionName::RentalsCancel->value);
    }
}
