<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\AdditionalCharge;
use App\Models\User;

class AdditionalChargePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::ChargesView->value);
    }

    public function view(User $user, AdditionalCharge $charge): bool
    {
        return $user->hasPermission(PermissionName::ChargesView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::ChargesCreate->value);
    }

    public function approve(User $user, AdditionalCharge $charge): bool
    {
        return $user->hasPermission(PermissionName::ChargesApprove->value);
    }

    public function reject(User $user, AdditionalCharge $charge): bool
    {
        return $user->hasPermission(PermissionName::ChargesReject->value);
    }
}
