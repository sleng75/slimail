<?php

namespace App\Traits;

use App\Helpers\TenantHelper;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Automatically set tenant_id when creating
        static::creating(function (Model $model) {
            if (empty($model->tenant_id)) {
                // Try TenantHelper first, then fall back to auth user
                $tenantId = TenantHelper::currentId();
                if (!$tenantId && auth()->check()) {
                    $tenantId = auth()->user()->tenant_id;
                }
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });

        // Add global scope to filter by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = auth()->user();

            // Don't apply scope for super admins
            if ($user && $user->isSuperAdmin()) {
                return;
            }

            $tenantId = TenantHelper::currentId();
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });
    }

    /**
     * Get the tenant that owns this model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to a specific tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Remove the tenant scope for this query.
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
