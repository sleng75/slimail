<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationStep extends Model
{
    use HasFactory;

    // Step types
    public const TYPE_SEND_EMAIL = 'send_email';
    public const TYPE_WAIT = 'wait';
    public const TYPE_CONDITION = 'condition';
    public const TYPE_ADD_TAG = 'add_tag';
    public const TYPE_REMOVE_TAG = 'remove_tag';
    public const TYPE_ADD_TO_LIST = 'add_to_list';
    public const TYPE_REMOVE_FROM_LIST = 'remove_from_list';
    public const TYPE_UPDATE_FIELD = 'update_field';
    public const TYPE_WEBHOOK = 'webhook';
    public const TYPE_GOAL = 'goal';
    public const TYPE_EXIT = 'exit';

    // Wait types
    public const WAIT_DURATION = 'duration';
    public const WAIT_UNTIL_DATE = 'until_date';
    public const WAIT_UNTIL_TIME = 'until_time';

    // Condition types
    public const CONDITION_TAG = 'has_tag';
    public const CONDITION_LIST = 'in_list';
    public const CONDITION_FIELD = 'field_value';
    public const CONDITION_EMAIL_OPENED = 'email_opened';
    public const CONDITION_LINK_CLICKED = 'link_clicked';

    protected $fillable = [
        'automation_id',
        'type',
        'name',
        'config',
        'position',
        'parent_step_id',
        'branch',
        'entered_count',
        'completed_count',
        'failed_count',
        'emails_sent',
        'emails_opened',
        'emails_clicked',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    protected $appends = [
        'type_label',
        'type_icon',
    ];

    /**
     * Get available step types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_SEND_EMAIL => [
                'label' => 'Envoyer un email',
                'icon' => 'envelope',
                'color' => 'blue',
                'category' => 'action',
            ],
            self::TYPE_WAIT => [
                'label' => 'Attendre',
                'icon' => 'clock',
                'color' => 'gray',
                'category' => 'flow',
            ],
            self::TYPE_CONDITION => [
                'label' => 'Condition Si/Alors',
                'icon' => 'code-branch',
                'color' => 'purple',
                'category' => 'flow',
            ],
            self::TYPE_ADD_TAG => [
                'label' => 'Ajouter un tag',
                'icon' => 'tag',
                'color' => 'green',
                'category' => 'action',
            ],
            self::TYPE_REMOVE_TAG => [
                'label' => 'Retirer un tag',
                'icon' => 'tag',
                'color' => 'red',
                'category' => 'action',
            ],
            self::TYPE_ADD_TO_LIST => [
                'label' => 'Ajouter à une liste',
                'icon' => 'list',
                'color' => 'green',
                'category' => 'action',
            ],
            self::TYPE_REMOVE_FROM_LIST => [
                'label' => 'Retirer d\'une liste',
                'icon' => 'list',
                'color' => 'red',
                'category' => 'action',
            ],
            self::TYPE_UPDATE_FIELD => [
                'label' => 'Mettre à jour un champ',
                'icon' => 'edit',
                'color' => 'yellow',
                'category' => 'action',
            ],
            self::TYPE_WEBHOOK => [
                'label' => 'Appeler un webhook',
                'icon' => 'globe',
                'color' => 'indigo',
                'category' => 'action',
            ],
            self::TYPE_GOAL => [
                'label' => 'Objectif atteint',
                'icon' => 'flag-checkered',
                'color' => 'emerald',
                'category' => 'flow',
            ],
            self::TYPE_EXIT => [
                'label' => 'Sortir de l\'automatisation',
                'icon' => 'sign-out-alt',
                'color' => 'red',
                'category' => 'flow',
            ],
        ];
    }

    /**
     * Get condition types.
     */
    public static function getConditionTypes(): array
    {
        return [
            self::CONDITION_TAG => 'Possède le tag',
            self::CONDITION_LIST => 'Est dans la liste',
            self::CONDITION_FIELD => 'Valeur d\'un champ',
            self::CONDITION_EMAIL_OPENED => 'A ouvert l\'email',
            self::CONDITION_LINK_CLICKED => 'A cliqué sur le lien',
        ];
    }

    /**
     * Get type label attribute.
     */
    public function getTypeLabelAttribute(): string
    {
        $types = self::getTypes();
        return $types[$this->type]['label'] ?? $this->type;
    }

    /**
     * Get type icon attribute.
     */
    public function getTypeIconAttribute(): string
    {
        $types = self::getTypes();
        return $types[$this->type]['icon'] ?? 'cog';
    }

    /**
     * Get type color attribute.
     */
    public function getTypeColorAttribute(): string
    {
        $types = self::getTypes();
        return $types[$this->type]['color'] ?? 'gray';
    }

    /**
     * Automation relationship.
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    /**
     * Parent step relationship.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AutomationStep::class, 'parent_step_id');
    }

    /**
     * Children steps relationship.
     */
    public function children(): HasMany
    {
        return $this->hasMany(AutomationStep::class, 'parent_step_id')->orderBy('position');
    }

    /**
     * Get yes branch children (for conditions).
     */
    public function yesBranch(): HasMany
    {
        return $this->children()->where('branch', 'yes');
    }

    /**
     * Get no branch children (for conditions).
     */
    public function noBranch(): HasMany
    {
        return $this->children()->where('branch', 'no');
    }

    /**
     * Email template relationship (for send_email steps).
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'config->template_id');
    }

    /**
     * Check if this is a condition step.
     */
    public function isCondition(): bool
    {
        return $this->type === self::TYPE_CONDITION;
    }

    /**
     * Check if this is a wait step.
     */
    public function isWait(): bool
    {
        return $this->type === self::TYPE_WAIT;
    }

    /**
     * Check if this is an email step.
     */
    public function isEmail(): bool
    {
        return $this->type === self::TYPE_SEND_EMAIL;
    }

    /**
     * Check if this is an exit step.
     */
    public function isExit(): bool
    {
        return $this->type === self::TYPE_EXIT;
    }

    /**
     * Get the next step for a contact.
     */
    public function getNextStep(?string $branch = null): ?AutomationStep
    {
        if ($this->isCondition() && $branch) {
            return $this->children()->where('branch', $branch)->first();
        }

        // Get next sibling step
        $sibling = AutomationStep::where('automation_id', $this->automation_id)
            ->where('parent_step_id', $this->parent_step_id)
            ->where('branch', $this->branch)
            ->where('position', '>', $this->position)
            ->orderBy('position')
            ->first();

        if ($sibling) {
            return $sibling;
        }

        // If no sibling, check parent's next step
        if ($this->parent) {
            return $this->parent->getNextStep();
        }

        return null;
    }

    /**
     * Calculate wait duration in seconds.
     */
    public function getWaitDurationSeconds(): int
    {
        if (!$this->isWait()) {
            return 0;
        }

        $config = $this->config;
        $waitType = $config['wait_type'] ?? self::WAIT_DURATION;

        switch ($waitType) {
            case self::WAIT_DURATION:
                $value = $config['duration_value'] ?? 1;
                $unit = $config['duration_unit'] ?? 'hours';

                return match ($unit) {
                    'minutes' => $value * 60,
                    'hours' => $value * 3600,
                    'days' => $value * 86400,
                    'weeks' => $value * 604800,
                    default => $value * 3600,
                };

            case self::WAIT_UNTIL_TIME:
                // Wait until specific time of day
                $time = $config['time'] ?? '09:00';
                $target = now()->setTimeFromTimeString($time);

                if ($target->isPast()) {
                    $target->addDay();
                }

                return $target->diffInSeconds(now());

            case self::WAIT_UNTIL_DATE:
                // Wait until specific date field value
                return 0; // Calculated at runtime based on contact

            default:
                return 3600;
        }
    }

    /**
     * Evaluate condition for a contact.
     */
    public function evaluateCondition(Contact $contact): bool
    {
        if (!$this->isCondition()) {
            return true;
        }

        $config = $this->config;
        $conditionType = $config['condition_type'] ?? '';

        switch ($conditionType) {
            case self::CONDITION_TAG:
                $tagId = $config['tag_id'] ?? null;
                return $tagId && $contact->tags()->where('tags.id', $tagId)->exists();

            case self::CONDITION_LIST:
                $listId = $config['list_id'] ?? null;
                return $listId && $contact->lists()->where('contact_lists.id', $listId)->exists();

            case self::CONDITION_FIELD:
                $field = $config['field'] ?? null;
                $operator = $config['operator'] ?? 'equals';
                $value = $config['value'] ?? null;

                if (!$field) {
                    return false;
                }

                $contactValue = $contact->getCustomField($field) ?? $contact->{$field} ?? null;

                return match ($operator) {
                    'equals' => $contactValue == $value,
                    'not_equals' => $contactValue != $value,
                    'contains' => str_contains((string)$contactValue, (string)$value),
                    'not_contains' => !str_contains((string)$contactValue, (string)$value),
                    'starts_with' => str_starts_with((string)$contactValue, (string)$value),
                    'ends_with' => str_ends_with((string)$contactValue, (string)$value),
                    'is_empty' => empty($contactValue),
                    'is_not_empty' => !empty($contactValue),
                    'greater_than' => $contactValue > $value,
                    'less_than' => $contactValue < $value,
                    default => false,
                };

            case self::CONDITION_EMAIL_OPENED:
                // Check if a specific email was opened
                $emailStepId = $config['email_step_id'] ?? null;
                // Would need to check email events
                return false;

            case self::CONDITION_LINK_CLICKED:
                // Check if a specific link was clicked
                $linkUrl = $config['link_url'] ?? null;
                // Would need to check email events
                return false;

            default:
                return false;
        }
    }

    /**
     * Increment step statistics.
     */
    public function incrementStat(string $stat, int $amount = 1): void
    {
        if (in_array($stat, ['entered_count', 'completed_count', 'failed_count', 'emails_sent', 'emails_opened', 'emails_clicked'])) {
            $this->increment($stat, $amount);
        }
    }

    /**
     * Get open rate for email steps.
     */
    public function getOpenRateAttribute(): float
    {
        if (!$this->isEmail() || $this->emails_sent === 0) {
            return 0;
        }

        return round(($this->emails_opened / $this->emails_sent) * 100, 2);
    }

    /**
     * Get click rate for email steps.
     */
    public function getClickRateAttribute(): float
    {
        if (!$this->isEmail() || $this->emails_sent === 0) {
            return 0;
        }

        return round(($this->emails_clicked / $this->emails_sent) * 100, 2);
    }

    /**
     * Get completion rate.
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->entered_count === 0) {
            return 0;
        }

        return round(($this->completed_count / $this->entered_count) * 100, 2);
    }
}
