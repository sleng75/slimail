<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    use HasFactory, BelongsToTenant;

    // Event type constants
    public const TYPE_SEND = 'send';
    public const TYPE_DELIVERY = 'delivery';
    public const TYPE_OPEN = 'open';
    public const TYPE_CLICK = 'click';
    public const TYPE_BOUNCE = 'bounce';
    public const TYPE_COMPLAINT = 'complaint';
    public const TYPE_REJECT = 'reject';
    public const TYPE_UNSUBSCRIBE = 'unsubscribe';
    public const TYPE_RENDERING_FAILURE = 'rendering_failure';
    public const TYPE_DELIVERY_DELAY = 'delivery_delay';

    protected $fillable = [
        'tenant_id',
        'sent_email_id',
        'contact_id',
        'campaign_id',
        'event_type',
        'message_id',
        'event_at',
        'ip_address',
        'user_agent',
        'link_url',
        'link_tag',
        'bounce_type',
        'bounce_subtype',
        'complaint_feedback_type',
        'country',
        'city',
        'region',
        'device_type',
        'client_name',
        'client_os',
        'raw_data',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Get the sent email.
     */
    public function sentEmail(): BelongsTo
    {
        return $this->belongsTo(SentEmail::class);
    }

    /**
     * Get the contact.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Check if this is a positive event (delivery, open, click).
     */
    public function isPositive(): bool
    {
        return in_array($this->event_type, [
            self::TYPE_DELIVERY,
            self::TYPE_OPEN,
            self::TYPE_CLICK,
        ]);
    }

    /**
     * Check if this is a negative event (bounce, complaint).
     */
    public function isNegative(): bool
    {
        return in_array($this->event_type, [
            self::TYPE_BOUNCE,
            self::TYPE_COMPLAINT,
            self::TYPE_REJECT,
        ]);
    }

    /**
     * Scope by event type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope for opens.
     */
    public function scopeOpens($query)
    {
        return $query->where('event_type', self::TYPE_OPEN);
    }

    /**
     * Scope for clicks.
     */
    public function scopeClicks($query)
    {
        return $query->where('event_type', self::TYPE_CLICK);
    }

    /**
     * Scope for bounces.
     */
    public function scopeBounces($query)
    {
        return $query->where('event_type', self::TYPE_BOUNCE);
    }

    /**
     * Scope for complaints.
     */
    public function scopeComplaints($query)
    {
        return $query->where('event_type', self::TYPE_COMPLAINT);
    }
}
