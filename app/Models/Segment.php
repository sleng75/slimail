<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Segment extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    // Match types
    public const MATCH_ALL = 'all'; // AND logic
    public const MATCH_ANY = 'any'; // OR logic

    // Supported operators
    public const OPERATORS = [
        'equals' => 'Est égal à',
        'not_equals' => 'N\'est pas égal à',
        'contains' => 'Contient',
        'not_contains' => 'Ne contient pas',
        'starts_with' => 'Commence par',
        'ends_with' => 'Termine par',
        'greater_than' => 'Supérieur à',
        'less_than' => 'Inférieur à',
        'greater_or_equal' => 'Supérieur ou égal à',
        'less_or_equal' => 'Inférieur ou égal à',
        'is_empty' => 'Est vide',
        'is_not_empty' => 'N\'est pas vide',
        'in_list' => 'Est dans la liste',
        'not_in_list' => 'N\'est pas dans la liste',
        'has_tag' => 'A le tag',
        'not_has_tag' => 'N\'a pas le tag',
        'before' => 'Avant',
        'after' => 'Après',
        'in_last_days' => 'Dans les X derniers jours',
    ];

    // Standard fields that can be used in criteria
    public const STANDARD_FIELDS = [
        'email' => ['label' => 'Email', 'type' => 'string'],
        'first_name' => ['label' => 'Prénom', 'type' => 'string'],
        'last_name' => ['label' => 'Nom', 'type' => 'string'],
        'phone' => ['label' => 'Téléphone', 'type' => 'string'],
        'company' => ['label' => 'Entreprise', 'type' => 'string'],
        'job_title' => ['label' => 'Fonction', 'type' => 'string'],
        'city' => ['label' => 'Ville', 'type' => 'string'],
        'country' => ['label' => 'Pays', 'type' => 'string'],
        'postal_code' => ['label' => 'Code postal', 'type' => 'string'],
        'status' => ['label' => 'Statut', 'type' => 'select', 'options' => ['subscribed', 'unsubscribed', 'bounced', 'complained']],
        'source' => ['label' => 'Source', 'type' => 'select', 'options' => ['manual', 'import', 'api', 'form']],
        'engagement_score' => ['label' => 'Score d\'engagement', 'type' => 'number'],
        'emails_sent' => ['label' => 'Emails envoyés', 'type' => 'number'],
        'emails_opened' => ['label' => 'Emails ouverts', 'type' => 'number'],
        'emails_clicked' => ['label' => 'Emails cliqués', 'type' => 'number'],
        'created_at' => ['label' => 'Date de création', 'type' => 'date'],
        'subscribed_at' => ['label' => 'Date d\'inscription', 'type' => 'date'],
        'last_email_sent_at' => ['label' => 'Dernier email envoyé', 'type' => 'date'],
        'last_email_opened_at' => ['label' => 'Dernière ouverture', 'type' => 'date'],
        'last_email_clicked_at' => ['label' => 'Dernier clic', 'type' => 'date'],
    ];

    // Special fields
    public const SPECIAL_FIELDS = [
        'list' => ['label' => 'Liste', 'type' => 'list'],
        'tag' => ['label' => 'Tag', 'type' => 'tag'],
        'custom_field' => ['label' => 'Champ personnalisé', 'type' => 'custom'],
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'criteria',
        'match_type',
        'cached_count',
        'count_cached_at',
        'is_active',
    ];

    protected $casts = [
        'criteria' => 'array',
        'cached_count' => 'integer',
        'count_cached_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['contact_count'];

    /**
     * Get the campaigns using this segment.
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_segment')
            ->withTimestamps();
    }

    /**
     * Get the contact count (cached or calculated).
     */
    public function getContactCountAttribute(): int
    {
        // Return cached count if fresh (less than 5 minutes old)
        if ($this->count_cached_at && $this->count_cached_at->gt(now()->subMinutes(5))) {
            return $this->cached_count;
        }

        return $this->cached_count;
    }

    /**
     * Refresh the cached contact count.
     */
    public function refreshCount(): int
    {
        $count = $this->getContactsQuery()->count();

        $this->update([
            'cached_count' => $count,
            'count_cached_at' => now(),
        ]);

        return $count;
    }

    /**
     * Get the query builder for contacts matching this segment.
     */
    public function getContactsQuery()
    {
        $query = Contact::where('tenant_id', $this->tenant_id)
            ->whereNull('deleted_at');

        $criteria = $this->criteria ?? [];

        if (empty($criteria)) {
            return $query;
        }

        if ($this->match_type === self::MATCH_ALL) {
            // AND logic - all criteria must match
            foreach ($criteria as $criterion) {
                $this->applyCriterion($query, $criterion);
            }
        } else {
            // OR logic - any criterion must match
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria as $index => $criterion) {
                    if ($index === 0) {
                        $this->applyCriterion($q, $criterion);
                    } else {
                        $q->orWhere(function ($subQ) use ($criterion) {
                            $this->applyCriterion($subQ, $criterion);
                        });
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Get contacts matching this segment.
     */
    public function getContacts()
    {
        return $this->getContactsQuery()->get();
    }

    /**
     * Get contact IDs matching this segment.
     */
    public function getContactIds(): array
    {
        return $this->getContactsQuery()->pluck('id')->toArray();
    }

    /**
     * Apply a single criterion to the query.
     */
    protected function applyCriterion($query, array $criterion): void
    {
        $field = $criterion['field'] ?? null;
        $operator = $criterion['operator'] ?? 'equals';
        $value = $criterion['value'] ?? null;

        if (!$field) {
            return;
        }

        // Handle special fields
        if ($field === 'list') {
            $this->applyListCriterion($query, $operator, $value);
            return;
        }

        if ($field === 'tag') {
            $this->applyTagCriterion($query, $operator, $value);
            return;
        }

        if (str_starts_with($field, 'custom_field.')) {
            $customField = str_replace('custom_field.', '', $field);
            $this->applyCustomFieldCriterion($query, $customField, $operator, $value);
            return;
        }

        // Handle standard fields
        $this->applyStandardFieldCriterion($query, $field, $operator, $value);
    }

    /**
     * Apply standard field criterion.
     */
    protected function applyStandardFieldCriterion($query, string $field, string $operator, $value): void
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, '=', $value);
                break;

            case 'not_equals':
                $query->where($field, '!=', $value);
                break;

            case 'contains':
                $query->where($field, 'LIKE', "%{$value}%");
                break;

            case 'not_contains':
                $query->where($field, 'NOT LIKE', "%{$value}%");
                break;

            case 'starts_with':
                $query->where($field, 'LIKE', "{$value}%");
                break;

            case 'ends_with':
                $query->where($field, 'LIKE', "%{$value}");
                break;

            case 'greater_than':
                $query->where($field, '>', $value);
                break;

            case 'less_than':
                $query->where($field, '<', $value);
                break;

            case 'greater_or_equal':
                $query->where($field, '>=', $value);
                break;

            case 'less_or_equal':
                $query->where($field, '<=', $value);
                break;

            case 'is_empty':
                $query->where(function ($q) use ($field) {
                    $q->whereNull($field)->orWhere($field, '=', '');
                });
                break;

            case 'is_not_empty':
                $query->whereNotNull($field)->where($field, '!=', '');
                break;

            case 'before':
                $query->where($field, '<', $value);
                break;

            case 'after':
                $query->where($field, '>', $value);
                break;

            case 'in_last_days':
                $query->where($field, '>=', now()->subDays((int) $value));
                break;
        }
    }

    /**
     * Apply list criterion.
     */
    protected function applyListCriterion($query, string $operator, $value): void
    {
        $listId = (int) $value;

        if ($operator === 'in_list') {
            $query->whereHas('lists', function ($q) use ($listId) {
                $q->where('contact_lists.id', $listId)
                    ->where('contact_list_members.status', 'active');
            });
        } elseif ($operator === 'not_in_list') {
            $query->whereDoesntHave('lists', function ($q) use ($listId) {
                $q->where('contact_lists.id', $listId)
                    ->where('contact_list_members.status', 'active');
            });
        }
    }

    /**
     * Apply tag criterion.
     */
    protected function applyTagCriterion($query, string $operator, $value): void
    {
        $tagId = (int) $value;

        if ($operator === 'has_tag') {
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        } elseif ($operator === 'not_has_tag') {
            $query->whereDoesntHave('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }
    }

    /**
     * Apply custom field criterion.
     */
    protected function applyCustomFieldCriterion($query, string $field, string $operator, $value): void
    {
        $jsonPath = "custom_fields->'{$field}'";

        switch ($operator) {
            case 'equals':
                $query->whereJsonContains('custom_fields', [$field => $value]);
                break;

            case 'not_equals':
                $query->where(function ($q) use ($field, $value) {
                    $q->whereJsonDoesntContain('custom_fields', [$field => $value])
                        ->orWhereNull("custom_fields->{$field}");
                });
                break;

            case 'contains':
                $query->whereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$field}')) LIKE ?",
                    ["%{$value}%"]
                );
                break;

            case 'is_empty':
                $query->where(function ($q) use ($field) {
                    $q->whereNull("custom_fields->{$field}")
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$field}')) = ''");
                });
                break;

            case 'is_not_empty':
                $query->whereNotNull("custom_fields->{$field}")
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$field}')) != ''");
                break;
        }
    }

    /**
     * Scope for active segments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get available fields for segment builder.
     */
    public static function getAvailableFields(int $tenantId): array
    {
        $fields = [];

        // Add standard fields
        foreach (self::STANDARD_FIELDS as $key => $config) {
            $fields[] = [
                'value' => $key,
                'label' => $config['label'],
                'type' => $config['type'],
                'options' => $config['options'] ?? null,
            ];
        }

        // Add list field
        $lists = ContactList::where('tenant_id', $tenantId)->get(['id', 'name']);
        $fields[] = [
            'value' => 'list',
            'label' => 'Liste',
            'type' => 'list',
            'options' => $lists->map(fn($l) => ['value' => $l->id, 'label' => $l->name]),
        ];

        // Add tag field
        $tags = Tag::where('tenant_id', $tenantId)->get(['id', 'name']);
        $fields[] = [
            'value' => 'tag',
            'label' => 'Tag',
            'type' => 'tag',
            'options' => $tags->map(fn($t) => ['value' => $t->id, 'label' => $t->name]),
        ];

        // Add custom fields from contacts
        $customFields = Contact::where('tenant_id', $tenantId)
            ->whereNotNull('custom_fields')
            ->limit(100)
            ->get(['custom_fields'])
            ->pluck('custom_fields')
            ->filter()
            ->flatMap(fn($cf) => array_keys($cf))
            ->unique()
            ->values();

        foreach ($customFields as $cf) {
            $fields[] = [
                'value' => "custom_field.{$cf}",
                'label' => "Personnalisé: {$cf}",
                'type' => 'custom',
            ];
        }

        return $fields;
    }

    /**
     * Get operators for a field type.
     */
    public static function getOperatorsForType(string $type): array
    {
        $operators = [];

        switch ($type) {
            case 'string':
            case 'custom':
                $operators = ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty'];
                break;

            case 'number':
                $operators = ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal'];
                break;

            case 'date':
                $operators = ['equals', 'before', 'after', 'in_last_days', 'is_empty', 'is_not_empty'];
                break;

            case 'select':
                $operators = ['equals', 'not_equals'];
                break;

            case 'list':
                $operators = ['in_list', 'not_in_list'];
                break;

            case 'tag':
                $operators = ['has_tag', 'not_has_tag'];
                break;
        }

        return collect($operators)->map(fn($op) => [
            'value' => $op,
            'label' => self::OPERATORS[$op] ?? $op,
        ])->toArray();
    }
}
