<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\GuideCategory;
use App\Models\User;

class GuideCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::GuideCategoriesManage->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, GuideCategory $category): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, GuideCategory $category): bool
    {
        return $this->viewAny($user);
    }
}
