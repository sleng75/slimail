<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    /**
     * Available permissions for API keys.
     */
    public const AVAILABLE_PERMISSIONS = [
        'send' => 'Envoyer des emails',
        'read' => 'Lire le statut des emails',
        'templates' => 'Utiliser les templates',
        'contacts' => 'Gérer les contacts',
    ];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'key',
        'secret_hash',
        'description',
        'permissions',
        'ip_whitelist',
        'last_used_at',
        'requests_count',
        'rate_limit',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'ip_whitelist' => 'array',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    /**
     * Generate a new API key pair.
     */
    public static function generateKeyPair(): array
    {
        $key = 'sk_' . Str::random(32);
        $secret = Str::random(48);

        return [
            'key' => $key,
            'secret' => $secret,
            'secret_hash' => hash('sha256', $secret),
        ];
    }

    /**
     * Verify the secret.
     */
    public function verifySecret(string $secret): bool
    {
        return hash('sha256', $secret) === $this->secret_hash;
    }

    /**
     * Check if the API key has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // No restrictions = all permissions
        }

        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    /**
     * Check if the IP is allowed.
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->ip_whitelist)) {
            return true; // No whitelist = all IPs allowed
        }

        return in_array($ip, $this->ip_whitelist);
    }

    /**
     * Check if the API key is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Record usage of this API key.
     */
    public function recordUsage(): void
    {
        $this->increment('requests_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get the creator of this API key.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant that owns this API key.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get a preview of the key (first 8 chars + asterisks).
     */
    public function getKeyPreviewAttribute(): string
    {
        if (!$this->key) {
            return '';
        }
        return substr($this->key, 0, 8) . '************************';
    }

    /**
     * Get the sent emails using this API key.
     */
    public function sentEmails(): HasMany
    {
        return $this->hasMany(SentEmail::class);
    }

    /**
     * Scope a query to only include active keys.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
