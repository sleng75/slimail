<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactList extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'color',
        'type',
        'segment_criteria',
        'double_optin',
        'welcome_email_content',
        'default_from_name',
        'default_from_email',
    ];

    protected $casts = [
        'segment_criteria' => 'array',
        'double_optin' => 'boolean',
    ];

    // List type constants
    public const TYPE_STATIC = 'static';
    public const TYPE_DYNAMIC = 'dynamic';

    // Default colors for lists
    public const COLORS = [
        '#dc2626', // red
        '#ea580c', // orange
        '#ca8a04', // yellow
        '#16a34a', // green
        '#0891b2', // cyan
        '#2563eb', // blue
        '#7c3aed', // violet
        '#c026d3', // fuchsia
    ];

    /**
     * The contacts that belong to this list.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_list_members')
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at'])
            ->withTimestamps();
    }

    /**
     * Get active contacts in this list.
     */
    public function activeContacts(): BelongsToMany
    {
        return $this->contacts()->wherePivot('status', 'active');
    }

    /**
     * Check if this is a static list.
     */
    public function isStatic(): bool
    {
        return $this->type === self::TYPE_STATIC;
    }

    /**
     * Check if this is a dynamic list.
     */
    public function isDynamic(): bool
    {
        return $this->type === self::TYPE_DYNAMIC;
    }

    /**
     * Add a contact to this list.
     */
    public function addContact(Contact $contact): void
    {
        if (!$this->contacts()->where('contact_id', $contact->id)->exists()) {
            $this->contacts()->attach($contact->id, [
                'status' => 'active',
                'subscribed_at' => now(),
            ]);
            $this->updateCounts();
        }
    }

    /**
     * Add multiple contacts to this list.
     */
    public function addContacts(array $contactIds): void
    {
        $existingIds = $this->contacts()->whereIn('contact_id', $contactIds)->pluck('contact_id')->toArray();
        $newIds = array_diff($contactIds, $existingIds);

        if (!empty($newIds)) {
            $attachData = [];
            foreach ($newIds as $id) {
                $attachData[$id] = [
                    'status' => 'active',
                    'subscribed_at' => now(),
                ];
            }
            $this->contacts()->attach($attachData);
            $this->updateCounts();
        }
    }

    /**
     * Remove a contact from this list.
     */
    public function removeContact(Contact $contact): void
    {
        $this->contacts()->detach($contact->id);
        $this->updateCounts();
    }

    /**
     * Remove multiple contacts from this list.
     */
    public function removeContacts(array $contactIds): void
    {
        $this->contacts()->detach($contactIds);
        $this->updateCounts();
    }

    /**
     * Update contact counts.
     */
    public function updateCounts(): void
    {
        $this->contacts_count = $this->contacts()->count();
        $this->subscribed_count = $this->contacts()
            ->wherePivot('status', 'active')
            ->where('contacts.status', Contact::STATUS_SUBSCRIBED)
            ->count();
        $this->unsubscribed_count = $this->contacts()
            ->wherePivot('status', 'unsubscribed')
            ->count();
        $this->save();
    }

    /**
     * Get contacts matching dynamic segment criteria.
     */
    public function getSegmentContacts()
    {
        if (!$this->isDynamic() || empty($this->segment_criteria)) {
            return Contact::query()->where('id', 0); // Empty query
        }

        $query = Contact::query();

        foreach ($this->segment_criteria as $criterion) {
            $field = $criterion['field'] ?? null;
            $operator = $criterion['operator'] ?? '=';
            $value = $criterion['value'] ?? null;

            if (!$field || $value === null) {
                continue;
            }

            switch ($operator) {
                case 'equals':
                    $query->where($field, $value);
                    break;
                case 'not_equals':
                    $query->where($field, '!=', $value);
                    break;
                case 'contains':
                    $query->where($field, 'like', "%{$value}%");
                    break;
                case 'not_contains':
                    $query->where($field, 'not like', "%{$value}%");
                    break;
                case 'starts_with':
                    $query->where($field, 'like', "{$value}%");
                    break;
                case 'ends_with':
                    $query->where($field, 'like', "%{$value}");
                    break;
                case 'is_empty':
                    $query->whereNull($field)->orWhere($field, '');
                    break;
                case 'is_not_empty':
                    $query->whereNotNull($field)->where($field, '!=', '');
                    break;
                case 'greater_than':
                    $query->where($field, '>', $value);
                    break;
                case 'less_than':
                    $query->where($field, '<', $value);
                    break;
                case 'in_list':
                    $query->whereHas('lists', function ($q) use ($value) {
                        $q->where('contact_lists.id', $value);
                    });
                    break;
                case 'has_tag':
                    $query->whereHas('tags', function ($q) use ($value) {
                        $q->where('tags.id', $value);
                    });
                    break;
            }
        }

        return $query;
    }

    /**
     * Scope a query to only include static lists.
     */
    public function scopeStatic($query)
    {
        return $query->where('type', self::TYPE_STATIC);
    }

    /**
     * Scope a query to only include dynamic lists.
     */
    public function scopeDynamic($query)
    {
        return $query->where('type', self::TYPE_DYNAMIC);
    }

    /**
     * Scope a query to search lists.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
