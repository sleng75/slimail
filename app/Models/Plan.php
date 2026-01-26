<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'emails_per_month',
        'contacts_limit',
        'campaigns_per_month',
        'templates_limit',
        'users_limit',
        'api_requests_per_day',
        'features',
        'sort_order',
        'is_popular',
        'is_active',
        'is_public',
        'trial_days',
        'stripe_price_id',
        'color',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'emails_per_month' => 'integer',
        'contacts_limit' => 'integer',
        'campaigns_per_month' => 'integer',
        'templates_limit' => 'integer',
        'users_limit' => 'integer',
        'api_requests_per_day' => 'integer',
        'features' => 'array',
        'sort_order' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'trial_days' => 'integer',
    ];

    /**
     * Default features structure.
     */
    public static array $defaultFeatures = [
        'email_editor' => true,
        'custom_domain' => false,
        'api_access' => false,
        'automation' => false,
        'ab_testing' => false,
        'advanced_analytics' => false,
        'priority_support' => false,
        'dedicated_ip' => false,
        'white_label' => false,
        'custom_branding' => false,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
            if (empty($plan->features)) {
                $plan->features = self::$defaultFeatures;
            }
        });
    }

    /**
     * Get the subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Scope for active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public plans.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Check if a feature is enabled.
     */
    public function hasFeature(string $feature): bool
    {
        return $this->features[$feature] ?? false;
    }

    /**
     * Get the price for a billing cycle.
     */
    public function getPrice(string $cycle = 'monthly'): float
    {
        return $cycle === 'yearly' ? $this->price_yearly : $this->price_monthly;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPrice(string $cycle = 'monthly'): string
    {
        $price = $this->getPrice($cycle);
        return number_format($price, 0, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get monthly equivalent for yearly pricing.
     */
    public function getMonthlyEquivalent(): float
    {
        return $this->price_yearly / 12;
    }

    /**
     * Get yearly savings percentage.
     */
    public function getYearlySavingsPercent(): int
    {
        if ($this->price_monthly <= 0) {
            return 0;
        }
        $yearlyFromMonthly = $this->price_monthly * 12;
        $savings = (($yearlyFromMonthly - $this->price_yearly) / $yearlyFromMonthly) * 100;
        return (int) round($savings);
    }

    /**
     * Check if this is a free plan.
     */
    public function isFree(): bool
    {
        return $this->price_monthly == 0 && $this->price_yearly == 0;
    }

    /**
     * Get limit display text.
     */
    public function getLimitText(string $type): string
    {
        $value = match($type) {
            'emails' => $this->emails_per_month,
            'contacts' => $this->contacts_limit,
            'campaigns' => $this->campaigns_per_month,
            'templates' => $this->templates_limit,
            'users' => $this->users_limit,
            'api' => $this->api_requests_per_day,
            default => 0,
        };

        return $value === 0 ? 'Illimité' : number_format($value, 0, ',', ' ');
    }
}
