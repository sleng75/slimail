<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'color',
        'description',
    ];

    // Default colors for tags
    public const COLORS = [
        '#dc2626', // red
        '#ea580c', // orange
        '#ca8a04', // yellow
        '#16a34a', // green
        '#0891b2', // cyan
        '#2563eb', // blue
        '#7c3aed', // violet
        '#c026d3', // fuchsia
        '#64748b', // slate
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
            if (empty($tag->color)) {
                $tag->color = self::COLORS[array_rand(self::COLORS)];
            }
        });

        static::updating(function (Tag $tag) {
            if ($tag->isDirty('name') && !$tag->isDirty('slug')) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * The contacts that have this tag.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_tags')
            ->withPivot('tagged_at');
    }

    /**
     * Add this tag to a contact.
     */
    public function addToContact(Contact $contact): void
    {
        if (!$this->contacts()->where('contact_id', $contact->id)->exists()) {
            $this->contacts()->attach($contact->id, ['tagged_at' => now()]);
            $this->updateCount();
        }
    }

    /**
     * Add this tag to multiple contacts.
     */
    public function addToContacts(array $contactIds): void
    {
        $existingIds = $this->contacts()->whereIn('contact_id', $contactIds)->pluck('contact_id')->toArray();
        $newIds = array_diff($contactIds, $existingIds);

        if (!empty($newIds)) {
            $attachData = [];
            foreach ($newIds as $id) {
                $attachData[$id] = ['tagged_at' => now()];
            }
            $this->contacts()->attach($attachData);
            $this->updateCount();
        }
    }

    /**
     * Remove this tag from a contact.
     */
    public function removeFromContact(Contact $contact): void
    {
        $this->contacts()->detach($contact->id);
        $this->updateCount();
    }

    /**
     * Remove this tag from multiple contacts.
     */
    public function removeFromContacts(array $contactIds): void
    {
        $this->contacts()->detach($contactIds);
        $this->updateCount();
    }

    /**
     * Update contact count.
     */
    public function updateCount(): void
    {
        $this->contacts_count = $this->contacts()->count();
        $this->save();
    }

    /**
     * Find or create a tag by name.
     */
    public static function findOrCreateByName(string $name, ?int $tenantId = null): self
    {
        $slug = Str::slug($name);

        return static::firstOrCreate(
            [
                'tenant_id' => $tenantId ?? auth()->user()?->tenant_id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
            ]
        );
    }

    /**
     * Scope a query to search tags.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        });
    }
}
