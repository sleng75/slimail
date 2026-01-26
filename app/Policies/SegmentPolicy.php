<?php

namespace App\Policies;

use App\Models\Segment;
use App\Models\User;

class SegmentPolicy
{
    /**
     * Determine whether the user can view any segments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contacts.view');
    }

    /**
     * Determine whether the user can view the segment.
     */
    public function view(User $user, Segment $segment): bool
    {
        return $user->tenant_id === $segment->tenant_id
            && $user->hasPermission('contacts.view');
    }

    /**
     * Determine whether the user can create segments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('contacts.manage');
    }

    /**
     * Determine whether the user can update the segment.
     */
    public function update(User $user, Segment $segment): bool
    {
        return $user->tenant_id === $segment->tenant_id
            && $user->hasPermission('contacts.manage');
    }

    /**
     * Determine whether the user can delete the segment.
     */
    public function delete(User $user, Segment $segment): bool
    {
        return $user->tenant_id === $segment->tenant_id
            && $user->hasPermission('contacts.manage');
    }
}
