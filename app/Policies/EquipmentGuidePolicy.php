<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\EquipmentGuide;
use App\Models\User;

class EquipmentGuidePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::GuidesView->value);
    }

    public function view(User $user, EquipmentGuide $guide): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::GuidesCreate->value);
    }

    public function update(User $user, EquipmentGuide $guide): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::GuidesUpdate->value);
    }

    public function publish(User $user, EquipmentGuide $guide): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::GuidesPublish->value);
    }

    public function archive(User $user, EquipmentGuide $guide): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::GuidesArchive->value);
    }

    public function delete(User $user, EquipmentGuide $guide): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::GuidesDelete->value);
    }
}
