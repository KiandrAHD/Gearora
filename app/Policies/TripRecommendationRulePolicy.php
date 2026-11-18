<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\TripRecommendationRule;
use App\Models\User;

class TripRecommendationRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::TripRecommendationsView->value);
    }

    public function view(User $user, TripRecommendationRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::TripRecommendationsCreate->value);
    }

    public function update(User $user, TripRecommendationRule $rule): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::TripRecommendationsUpdate->value);
    }

    public function delete(User $user, TripRecommendationRule $rule): bool
    {
        return $user->isOwner() || $user->hasPermission(PermissionName::TripRecommendationsDelete->value);
    }
}
