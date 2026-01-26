<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    // Type constants
    public const TYPE_REGULAR = 'regular';
    public const TYPE_AB_TEST = 'ab_test';
    public const TYPE_AUTOMATED = 'automated';

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
        'type',
        'from_name',
        'from_email',
        'reply_to',
        'subject',
        'preview_text',
        'html_content',
        'text_content',
        'template_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'timezone',
        'send_by_timezone',
        'list_ids',
        'segment_ids',
        'excluded_list_ids',
        'recipients_count',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'complained_count',
        'unsubscribed_count',
        'ab_test_config',
        'winning_variant_id',
        'track_opens',
        'track_clicks',
        'google_analytics',
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];

    protected $casts = [
        'list_ids' => 'array',
        'segment_ids' => 'array',
        'excluded_list_ids' => 'array',
        'ab_test_config' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'track_opens' => 'boolean',
        'track_clicks' => 'boolean',
        'google_analytics' => 'boolean',
        'send_by_timezone' => 'boolean',
    ];

    protected $appends = ['open_rate', 'click_rate', 'status_label', 'status_color'];

    /**
     * Get status label in French.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SCHEDULED => 'Programmée',
            self::STATUS_SENDING => 'En cours',
            self::STATUS_SENT => 'Envoyée',
            self::STATUS_PAUSED => 'En pause',
            self::STATUS_CANCELLED => 'Annulée',
            default => 'Inconnu',
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SCHEDULED => 'blue',
            self::STATUS_SENDING => 'yellow',
            self::STATUS_SENT => 'green',
            self::STATUS_PAUSED => 'orange',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get type label in French.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_REGULAR => 'Standard',
            self::TYPE_AB_TEST => 'Test A/B',
            self::TYPE_AUTOMATED => 'Automatisée',
            default => 'Standard',
        };
    }

    /**
     * Get the open rate.
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }
        return round(($this->opened_count / $this->delivered_count) * 100, 2);
    }

    /**
     * Get the click rate.
     */
    public function getClickRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }
        return round(($this->clicked_count / $this->delivered_count) * 100, 2);
    }

    /**
     * Get the bounce rate.
     */
    public function getBounceRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }
        return round(($this->bounced_count / $this->sent_count) * 100, 2);
    }

    /**
     * Get the delivery rate.
     */
    public function getDeliveryRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }
        return round(($this->delivered_count / $this->sent_count) * 100, 2);
    }

    /**
     * Check if the campaign is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    /**
     * Check if the campaign can be sent.
     */
    public function canBeSent(): bool
    {
        return $this->status === self::STATUS_DRAFT
            && $this->from_email
            && $this->subject
            && ($this->html_content || $this->template_id)
            && !empty($this->list_ids);
    }

    /**
     * Schedule the campaign.
     */
    public function schedule(\DateTime $datetime): void
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduled_at' => $datetime,
        ]);
    }

    /**
     * Start sending the campaign.
     */
    public function startSending(): void
    {
        $this->update([
            'status' => self::STATUS_SENDING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'completed_at' => now(),
        ]);
    }

    /**
     * Pause the campaign.
     */
    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Cancel the campaign.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Increment stats.
     */
    public function incrementStat(string $stat): void
    {
        $field = $stat . '_count';
        if (in_array($field, $this->fillable)) {
            $this->increment($field);
        }
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * Get the sent emails.
     */
    public function sentEmails(): HasMany
    {
        return $this->hasMany(SentEmail::class);
    }

    /**
     * Scope by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for drafts.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for scheduled campaigns.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope for sent campaigns.
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope for campaigns ready to be sent (scheduled and time passed).
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Search campaigns.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%");
        });
    }

    /**
     * Check if campaign is A/B test.
     */
    public function isAbTest(): bool
    {
        return $this->type === self::TYPE_AB_TEST;
    }

    /**
     * Check if campaign is ready to be sent.
     */
    public function isReadyToSend(): bool
    {
        return !empty($this->from_name)
            && !empty($this->from_email)
            && !empty($this->subject)
            && !empty($this->html_content)
            && (!empty($this->list_ids) || !empty($this->segment_ids))
            && $this->recipients_count > 0;
    }

    /**
     * Get the contact lists for this campaign.
     */
    public function getLists()
    {
        if (empty($this->list_ids)) {
            return collect();
        }
        return ContactList::whereIn('id', $this->list_ids)->get();
    }

    /**
     * Get all recipient contacts for this campaign.
     */
    public function getRecipients()
    {
        $query = Contact::subscribed();

        // Include contacts from lists
        if (!empty($this->list_ids)) {
            $query->whereHas('lists', function ($q) {
                $q->whereIn('contact_lists.id', $this->list_ids)
                    ->where('contact_list_members.status', 'active');
            });
        }

        // Exclude contacts from excluded lists
        if (!empty($this->excluded_list_ids)) {
            $query->whereDoesntHave('lists', function ($q) {
                $q->whereIn('contact_lists.id', $this->excluded_list_ids);
            });
        }

        return $query;
    }

    /**
     * Calculate and update recipients count.
     */
    public function updateRecipientsCount(): int
    {
        $count = $this->getRecipients()->count();
        $this->update(['recipients_count' => $count]);
        return $count;
    }

    /**
     * Resume sending a paused campaign.
     */
    public function resume(): void
    {
        $this->update(['status' => self::STATUS_SENDING]);
    }

    /**
     * Duplicate the campaign.
     */
    public function duplicate(): self
    {
        $newCampaign = $this->replicate([
            'status', 'scheduled_at', 'started_at', 'completed_at',
            'sent_count', 'delivered_count', 'opened_count', 'clicked_count',
            'bounced_count', 'complained_count', 'unsubscribed_count', 'recipients_count'
        ]);
        $newCampaign->name = $this->name . ' (copie)';
        $newCampaign->status = self::STATUS_DRAFT;
        $newCampaign->save();

        return $newCampaign;
    }

    /**
     * Get A/B test variants.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(CampaignVariant::class);
    }
}
