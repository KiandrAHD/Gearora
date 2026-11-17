<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::CategoriesView->value);
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasPermission(PermissionName::CategoriesView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::CategoriesCreate->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasPermission(PermissionName::CategoriesUpdate->value);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermission(PermissionName::CategoriesDelete->value);
    }
}
