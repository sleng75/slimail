<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Les rôles disponibles dans le système.
     */
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_ANALYST = 'analyst';
    public const ROLE_DEVELOPER = 'developer';

    /**
     * Permissions par rôle.
     */
    public const ROLE_PERMISSIONS = [
        self::ROLE_OWNER => [
            'tenant.manage',
            'users.manage',
            'billing.manage',
            'contacts.manage',
            'campaigns.manage',
            'templates.manage',
            'automations.manage',
            'statistics.view',
            'api.manage',
            'settings.manage',
        ],
        self::ROLE_ADMIN => [
            'users.manage',
            'contacts.manage',
            'campaigns.manage',
            'templates.manage',
            'automations.manage',
            'statistics.view',
            'api.manage',
            'settings.manage',
        ],
        self::ROLE_EDITOR => [
            'contacts.manage',
            'campaigns.manage',
            'templates.manage',
            'automations.manage',
            'statistics.view',
        ],
        self::ROLE_ANALYST => [
            'contacts.view',
            'campaigns.view',
            'statistics.view',
        ],
        self::ROLE_DEVELOPER => [
            'api.manage',
            'statistics.view',
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'is_super_admin',
        'phone',
        'avatar',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that the user belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if the user is a super admin (SLIMAT).
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * Check if the user is the owner of the tenant.
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if the user is an admin or higher.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = self::ROLE_PERMISSIONS[$this->role] ?? [];

        return in_array($permission, $permissions);
    }

    /**
     * Check if the user can manage the given resource.
     */
    public function canManage(string $resource): bool
    {
        return $this->hasPermission("{$resource}.manage");
    }

    /**
     * Check if the user can view the given resource.
     */
    public function canView(string $resource): bool
    {
        return $this->hasPermission("{$resource}.view") || $this->hasPermission("{$resource}.manage");
    }

    /**
     * Get the user's initials.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }

    /**
     * Get the available roles.
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_OWNER => 'Propriétaire',
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_EDITOR => 'Éditeur',
            self::ROLE_ANALYST => 'Analyste',
            self::ROLE_DEVELOPER => 'Développeur',
        ];
    }

    /**
     * Get the role label.
     */
    public function getRoleLabelAttribute(): string
    {
        if (empty($this->role)) {
            return 'Non défini';
        }
        return self::getRoles()[$this->role] ?? $this->role;
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }

        return $query;
    }
}
