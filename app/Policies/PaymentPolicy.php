<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::PaymentsView->value);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->getKey() === $payment->order?->customer_id) {
            return true;
        }

        return $user->hasPermission(PermissionName::PaymentsView->value);
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->hasPermission(PermissionName::PaymentsVerify->value);
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $user->hasPermission(PermissionName::PaymentsReject->value);
    }
}
