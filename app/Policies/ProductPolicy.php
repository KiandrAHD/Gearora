<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::ProductsView->value);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission(PermissionName::ProductsView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::ProductsCreate->value);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission(PermissionName::ProductsUpdate->value);
    }

    public function archive(User $user, Product $product): bool
    {
        return $user->hasPermission(PermissionName::ProductsArchive->value);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission(PermissionName::ProductsDelete->value);
    }
}
