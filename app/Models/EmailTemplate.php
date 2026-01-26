<?php

namespace App\Models;

use App\Helpers\TenantHelper;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Get the tenant that owns this model.
     */
    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Boot the model.
     * Custom tenant scope to include system templates.
     */
    protected static function booted(): void
    {
        // Auto-set tenant_id when creating (only for non-system templates)
        static::creating(function (Model $model) {
            if (empty($model->tenant_id) && !$model->is_system) {
                $tenantId = TenantHelper::currentId();
                if (!$tenantId && auth()->check()) {
                    $tenantId = auth()->user()->tenant_id;
                }
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });

        // Custom tenant scope that includes system templates
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = auth()->user();

            // Don't apply scope for super admins
            if ($user && $user->isSuperAdmin()) {
                return;
            }

            $tenantId = TenantHelper::currentId();
            if ($tenantId) {
                // Include both tenant-specific and system templates
                $builder->where(function ($query) use ($tenantId) {
                    $query->where('email_templates.tenant_id', $tenantId)
                        ->orWhere('email_templates.is_system', true);
                });
            } else {
                // If no tenant, only show system templates
                $builder->where('email_templates.is_system', true);
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
        'category',
        'thumbnail',
        'html_content',
        'text_content',
        'design_json',
        'default_subject',
        'default_from_name',
        'default_from_email',
        'is_active',
        'is_system',
        'usage_count',
    ];

    protected $casts = [
        'design_json' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Render the template with variables.
     */
    public function render(array $variables = []): string
    {
        $content = $this->html_content ?? '';

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }

        return $content;
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get campaigns using this template.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'template_id');
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for non-system templates (deletable).
     */
    public function scopeDeletable($query)
    {
        return $query->where('is_system', false);
    }
}
