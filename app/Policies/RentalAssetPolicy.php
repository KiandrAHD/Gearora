<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\RentalAsset;
use App\Models\User;

class RentalAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::InventoryView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::InventoryManageAssets->value);
    }

    public function update(User $user, RentalAsset $asset): bool
    {
        return $user->hasPermission(PermissionName::InventoryManageAssets->value);
    }
}
