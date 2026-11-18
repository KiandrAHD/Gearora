<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::PaymentsRefund->value);
    }

    public function view(User $user, Refund $refund): bool
    {
        return $user->hasPermission(PermissionName::PaymentsRefund->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::PaymentsRefund->value);
    }

    public function approve(User $user, Refund $refund): bool
    {
        return $user->hasPermission(PermissionName::PaymentsRefund->value);
    }

    public function process(User $user, Refund $refund): bool
    {
        return $user->hasPermission(PermissionName::PaymentsRefund->value);
    }
}
