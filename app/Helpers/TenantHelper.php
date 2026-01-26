<?php

namespace App\Helpers;

use App\Models\Tenant;
use Illuminate\Support\Facades\App;

class TenantHelper
{
    /**
     * Get the current tenant.
     */
    public static function current(): ?Tenant
    {
        if (App::has('currentTenant')) {
            return App::make('currentTenant');
        }

        $user = auth()->user();
        if ($user && $user->tenant_id) {
            return $user->tenant;
        }

        return null;
    }

    /**
     * Get the current tenant ID.
     */
    public static function currentId(): ?int
    {
        return static::current()?->id;
    }

    /**
     * Check if there is a current tenant.
     */
    public static function has(): bool
    {
        return static::current() !== null;
    }
}
