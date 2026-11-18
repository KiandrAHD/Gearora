<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::SalesView->value);
    }

    public function view(User $user, SalesOrder $order): bool
    {
        if ($user->isStaff()) {
            return $this->viewAny($user);
        }

        return $order->customer_id === $user->getKey();
    }

    public function update(User $user, SalesOrder $order): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::SalesUpdate->value);
    }

    public function cancel(User $user, SalesOrder $order): bool
    {
        if ($order->customer_id === $user->getKey()) {
            return true;
        }

        return $user->isOwner() || $user->hasPermission(PermissionName::SalesCancel->value);
    }

    public function complete(User $user, SalesOrder $order): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::SalesComplete->value);
    }
}
