<?php

namespace App\Models;

use App\Events\ContactSubscribedToList;
use App\Events\ContactTagAdded;
use App\Events\ContactTagRemoved;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    // Status constants
    public const STATUS_SUBSCRIBED = 'subscribed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_COMPLAINED = 'complained';

    // Source constants
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_IMPORT = 'import';
    public const SOURCE_API = 'api';
    public const SOURCE_FORM = 'form';

    protected $fillable = [
        'tenant_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'company',
        'job_title',
        'address',
        'city',
        'country',
        'postal_code',
        'custom_fields',
        'status',
        'subscribed_at',
        'unsubscribed_at',
        'unsubscribe_reason',
        'bounced_at',
        'complained_at',
        'source',
        'source_details',
        'engagement_score',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
        'last_email_sent_at' => 'datetime',
        'last_email_opened_at' => 'datetime',
        'last_email_clicked_at' => 'datetime',
        'engagement_score' => 'decimal:2',
    ];

    protected $appends = ['full_name'];

    /**
     * Get the contact's full name.
     */
    public function getFullNameAttribute(): string
    {
        $name = trim("{$this->first_name} {$this->last_name}");
        return $name ?: $this->email;
    }

    /**
     * Get the initials for avatar display.
     */
    public function getInitialsAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        }
        if ($this->first_name) {
            return strtoupper(substr($this->first_name, 0, 2));
        }
        return strtoupper(substr($this->email, 0, 2));
    }

    /**
     * The lists that the contact belongs to.
     */
    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(ContactList::class, 'contact_list_members')
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at'])
            ->withTimestamps();
    }

    /**
     * The tags assigned to this contact.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'contact_tags')
            ->withPivot('tagged_at');
    }

    /**
     * Check if contact is subscribed.
     */
    public function isSubscribed(): bool
    {
        return $this->status === self::STATUS_SUBSCRIBED;
    }

    /**
     * Check if contact can receive emails.
     */
    public function canReceiveEmails(): bool
    {
        return in_array($this->status, [self::STATUS_SUBSCRIBED]);
    }

    /**
     * Unsubscribe the contact.
     */
    public function unsubscribe(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
            'unsubscribe_reason' => $reason,
        ]);
    }

    /**
     * Mark contact as bounced.
     */
    public function markAsBounced(): void
    {
        $this->update([
            'status' => self::STATUS_BOUNCED,
        ]);
    }

    /**
     * Mark contact as complained.
     */
    public function markAsComplained(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLAINED,
        ]);
    }

    /**
     * Increment email sent count.
     */
    public function incrementEmailSent(): void
    {
        $this->increment('emails_sent');
        $this->update(['last_email_sent_at' => now()]);
    }

    /**
     * Increment email opened count.
     */
    public function incrementEmailOpened(): void
    {
        $this->increment('emails_opened');
        $this->update(['last_email_opened_at' => now()]);
        $this->recalculateEngagement();
    }

    /**
     * Increment email clicked count.
     */
    public function incrementEmailClicked(): void
    {
        $this->increment('emails_clicked');
        $this->update(['last_email_clicked_at' => now()]);
        $this->recalculateEngagement();
    }

    /**
     * Recalculate engagement score.
     */
    public function recalculateEngagement(): void
    {
        if ($this->emails_sent === 0) {
            $this->engagement_score = 0;
            return;
        }

        // Engagement formula: (opens * 1 + clicks * 2) / sent * 100
        $score = (($this->emails_opened * 1) + ($this->emails_clicked * 2)) / $this->emails_sent * 100;
        $this->engagement_score = min(100, $score);
        $this->save();
    }

    /**
     * Get a custom field value.
     */
    public function getCustomField(string $key, $default = null)
    {
        return data_get($this->custom_fields, $key, $default);
    }

    /**
     * Set a custom field value.
     */
    public function setCustomField(string $key, $value): void
    {
        $fields = $this->custom_fields ?? [];
        $fields[$key] = $value;
        $this->custom_fields = $fields;
        $this->save();
    }

    /**
     * Scope a query to only include subscribed contacts.
     */
    public function scopeSubscribed($query)
    {
        return $query->where('status', self::STATUS_SUBSCRIBED);
    }

    /**
     * Scope a query to only include contacts that can receive emails.
     */
    public function scopeCanReceiveEmails($query)
    {
        return $query->where('status', self::STATUS_SUBSCRIBED);
    }

    /**
     * Scope a query to search contacts.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('email', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by list.
     */
    public function scopeInList($query, int $listId)
    {
        return $query->whereHas('lists', function ($q) use ($listId) {
            $q->where('contact_lists.id', $listId)
                ->where('contact_list_members.status', 'active');
        });
    }

    /**
     * Scope a query to filter by tag.
     */
    public function scopeWithTag($query, int $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }

    /**
     * Subscribe contact to a list and trigger automation event.
     */
    public function subscribeToList(ContactList $list): void
    {
        $wasSubscribed = $this->lists()->where('contact_lists.id', $list->id)->exists();

        $this->lists()->syncWithoutDetaching([
            $list->id => [
                'status' => 'active',
                'subscribed_at' => now(),
            ]
        ]);

        $list->updateContactCount();

        // Trigger automation event only if this is a new subscription
        if (!$wasSubscribed) {
            event(new ContactSubscribedToList($this, $list));
        }
    }

    /**
     * Add a tag to the contact and trigger automation event.
     */
    public function addTag(Tag $tag): void
    {
        $hadTag = $this->tags()->where('tags.id', $tag->id)->exists();

        $this->tags()->syncWithoutDetaching([
            $tag->id => ['tagged_at' => now()]
        ]);

        // Trigger automation event only if tag was just added
        if (!$hadTag) {
            event(new ContactTagAdded($this, $tag));
        }
    }

    /**
     * Remove a tag from the contact and trigger automation event.
     */
    public function removeTag(Tag $tag): void
    {
        $hadTag = $this->tags()->where('tags.id', $tag->id)->exists();

        $this->tags()->detach($tag->id);

        // Trigger automation event only if tag was actually removed
        if ($hadTag) {
            event(new ContactTagRemoved($this, $tag));
        }
    }
}
