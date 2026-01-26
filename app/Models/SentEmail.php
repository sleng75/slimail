<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SentEmail extends Model
{
    use HasFactory, BelongsToTenant;

    // Status constants
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_OPENED = 'opened';
    public const STATUS_CLICKED = 'clicked';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_COMPLAINED = 'complained';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';

    // Type constants
    public const TYPE_TRANSACTIONAL = 'transactional';
    public const TYPE_CAMPAIGN = 'campaign';
    public const TYPE_AUTOMATION = 'automation';

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'campaign_id',
        'api_key_id',
        'message_id',
        'from_email',
        'from_name',
        'to_email',
        'to_name',
        'reply_to',
        'subject',
        'html_content',
        'text_content',
        'type',
        'status',
        'opens_count',
        'clicks_count',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'complained_at',
        'bounce_type',
        'bounce_subtype',
        'error_message',
        'headers',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'headers' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
    ];

    /**
     * Mark as sent.
     */
    public function markAsSent(string $messageId): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        $this->contact?->incrementEmailSent();
    }

    /**
     * Mark as opened.
     */
    public function markAsOpened(): void
    {
        $this->increment('opens_count');

        if (!$this->opened_at) {
            $this->update([
                'status' => self::STATUS_OPENED,
                'opened_at' => now(),
            ]);
        }

        $this->contact?->incrementEmailOpened();
    }

    /**
     * Mark as clicked.
     */
    public function markAsClicked(): void
    {
        $this->increment('clicks_count');

        if (!$this->clicked_at) {
            $this->update([
                'status' => self::STATUS_CLICKED,
                'clicked_at' => now(),
            ]);
        }

        $this->contact?->incrementEmailClicked();
    }

    /**
     * Mark as bounced.
     */
    public function markAsBounced(string $type, string $subtype, ?string $message = null): void
    {
        $this->update([
            'status' => self::STATUS_BOUNCED,
            'bounced_at' => now(),
            'bounce_type' => $type,
            'bounce_subtype' => $subtype,
            'error_message' => $message,
        ]);

        // Update contact status if permanent bounce
        if ($type === 'Permanent' && $this->contact) {
            $this->contact->markAsBounced();
        }
    }

    /**
     * Mark as complained.
     */
    public function markAsComplained(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLAINED,
            'complained_at' => now(),
        ]);

        $this->contact?->markAsComplained();
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * Get the contact.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the campaign.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the API key used.
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Get the events for this email.
     */
    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    /**
     * Scope by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for transactional emails.
     */
    public function scopeTransactional($query)
    {
        return $query->where('type', self::TYPE_TRANSACTIONAL);
    }

    /**
     * Scope for campaign emails.
     */
    public function scopeCampaign($query)
    {
        return $query->where('type', self::TYPE_CAMPAIGN);
    }
}
