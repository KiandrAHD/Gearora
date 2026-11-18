<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Deposit;
use App\Models\User;

class DepositPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::DepositsView->value);
    }

    public function view(User $user, Deposit $deposit): bool
    {
        if ($user->getKey() === $deposit->customer_id) {
            return true;
        }

        return $user->hasPermission(PermissionName::DepositsView->value);
    }

    public function manage(User $user, Deposit $deposit): bool
    {
        return $user->hasPermission(PermissionName::DepositsManage->value);
    }

    public function deduct(User $user, Deposit $deposit): bool
    {
        return $user->hasPermission(PermissionName::DepositsDeduct->value);
    }

    public function refund(User $user, Deposit $deposit): bool
    {
        return $user->hasPermission(PermissionName::DepositsRefund->value);
    }

    public function approve(User $user, Deposit $deposit): bool
    {
        return $user->hasPermission(PermissionName::DepositsApprove->value);
    }
}
