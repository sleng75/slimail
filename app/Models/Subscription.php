<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    // Status constants
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_EXPIRED = 'expired';

    // Billing cycle constants
    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_YEARLY = 'yearly';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'billing_cycle',
        'price',
        'currency',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
        'suspended_at',
        'emails_used',
        'contacts_count',
        'campaigns_used',
        'api_requests_today',
        'api_requests_reset_date',
        'cancellation_reason',
        'cancellation_feedback',
        'external_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'suspended_at' => 'datetime',
        'api_requests_reset_date' => 'date',
        'emails_used' => 'integer',
        'contacts_count' => 'integer',
        'campaigns_used' => 'integer',
        'api_requests_today' => 'integer',
    ];

    /**
     * Get the plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the invoices.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if subscription is on trial.
     */
    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIALING &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription has valid access (active or trial).
     */
    public function hasValidAccess(): bool
    {
        return $this->isActive() || $this->onTrial();
    }

    /**
     * Check if subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Check if subscription is past due.
     */
    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Get days remaining in current period.
     */
    public function daysRemaining(): int
    {
        if ($this->onTrial()) {
            return max(0, now()->diffInDays($this->trial_ends_at, false));
        }

        if ($this->ends_at) {
            return max(0, now()->diffInDays($this->ends_at, false));
        }

        return 0;
    }

    /**
     * Get days remaining in trial.
     */
    public function trialDaysRemaining(): int
    {
        if (!$this->onTrial()) {
            return 0;
        }
        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Check if can use more emails.
     */
    public function canSendEmails(int $count = 1): bool
    {
        if (!$this->hasValidAccess()) {
            return false;
        }

        $limit = $this->plan->emails_per_month;
        if ($limit === 0) {
            return true; // Unlimited
        }

        return ($this->emails_used + $count) <= $limit;
    }

    /**
     * Get remaining emails.
     */
    public function remainingEmails(): int
    {
        $limit = $this->plan->emails_per_month;
        if ($limit === 0) {
            return PHP_INT_MAX; // Unlimited
        }
        return max(0, $limit - $this->emails_used);
    }

    /**
     * Get email usage percentage.
     */
    public function emailUsagePercent(): float
    {
        $limit = $this->plan->emails_per_month;
        if ($limit === 0) {
            return 0;
        }
        return min(100, ($this->emails_used / $limit) * 100);
    }

    /**
     * Check if can add more contacts.
     */
    public function canAddContacts(int $count = 1): bool
    {
        if (!$this->hasValidAccess()) {
            return false;
        }

        $limit = $this->plan->contacts_limit;
        if ($limit === 0) {
            return true; // Unlimited
        }

        return ($this->contacts_count + $count) <= $limit;
    }

    /**
     * Get remaining contacts slots.
     */
    public function remainingContacts(): int
    {
        $limit = $this->plan->contacts_limit;
        if ($limit === 0) {
            return PHP_INT_MAX; // Unlimited
        }
        return max(0, $limit - $this->contacts_count);
    }

    /**
     * Get contact usage percentage.
     */
    public function contactUsagePercent(): float
    {
        $limit = $this->plan->contacts_limit;
        if ($limit === 0) {
            return 0;
        }
        return min(100, ($this->contacts_count / $limit) * 100);
    }

    /**
     * Check if has feature.
     */
    public function hasFeature(string $feature): bool
    {
        return $this->plan->hasFeature($feature);
    }

    /**
     * Increment email usage.
     */
    public function incrementEmailUsage(int $count = 1): void
    {
        $this->increment('emails_used', $count);
    }

    /**
     * Update contact count.
     */
    public function updateContactCount(int $count): void
    {
        $this->update(['contacts_count' => $count]);
    }

    /**
     * Increment API requests.
     */
    public function incrementApiRequests(): void
    {
        // Reset if it's a new day
        if ($this->api_requests_reset_date !== now()->toDateString()) {
            $this->update([
                'api_requests_today' => 1,
                'api_requests_reset_date' => now()->toDateString(),
            ]);
        } else {
            $this->increment('api_requests_today');
        }
    }

    /**
     * Check if can make API request.
     */
    public function canMakeApiRequest(): bool
    {
        if (!$this->hasValidAccess() || !$this->hasFeature('api_access')) {
            return false;
        }

        $limit = $this->plan->api_requests_per_day;
        if ($limit === 0) {
            return true; // Unlimited
        }

        // Reset count if new day
        if ($this->api_requests_reset_date !== now()->toDateString()) {
            return true;
        }

        return $this->api_requests_today < $limit;
    }

    /**
     * Reset monthly usage counters.
     */
    public function resetMonthlyUsage(): void
    {
        $this->update([
            'emails_used' => 0,
            'campaigns_used' => 0,
        ]);
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(string $reason = null, string $feedback = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELED,
            'canceled_at' => now(),
            'cancellation_reason' => $reason,
            'cancellation_feedback' => $feedback,
        ]);
    }

    /**
     * Suspend the subscription.
     */
    public function suspend(): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'suspended_at' => now(),
        ]);
    }

    /**
     * Reactivate the subscription.
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'suspended_at' => null,
            'canceled_at' => null,
        ]);
    }

    /**
     * Extend subscription.
     */
    public function extend(int $days): void
    {
        $newEndDate = $this->ends_at ? $this->ends_at->addDays($days) : now()->addDays($days);
        $this->update(['ends_at' => $newEndDate]);
    }

    /**
     * Renew subscription for another period.
     */
    public function renew(): void
    {
        $days = $this->billing_cycle === self::CYCLE_YEARLY ? 365 : 30;

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays($days),
            'emails_used' => 0,
            'campaigns_used' => 0,
        ]);
    }

    /**
     * Scope for active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for trialing subscriptions.
     */
    public function scopeTrialing($query)
    {
        return $query->where('status', self::STATUS_TRIALING);
    }

    /**
     * Scope for subscriptions expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope for expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now())
            ->whereNotIn('status', [self::STATUS_CANCELED, self::STATUS_EXPIRED]);
    }
}
