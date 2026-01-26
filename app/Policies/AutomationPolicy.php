<?php

namespace App\Policies;

use App\Models\Automation;
use App\Models\User;

class AutomationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Automation $automation): bool
    {
        return $user->tenant_id === $automation->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('automations.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Automation $automation): bool
    {
        return $user->tenant_id === $automation->tenant_id
            && $user->hasPermission('automations.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Automation $automation): bool
    {
        return $user->tenant_id === $automation->tenant_id
            && $user->hasPermission('automations.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Automation $automation): bool
    {
        return $user->tenant_id === $automation->tenant_id
            && $user->hasPermission('automations.manage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Automation $automation): bool
    {
        return $user->tenant_id === $automation->tenant_id
            && $user->isOwner();
    }
}
