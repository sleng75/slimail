<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Automation extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    // Trigger types
    public const TRIGGER_LIST_SUBSCRIPTION = 'list_subscription';
    public const TRIGGER_TAG_ADDED = 'tag_added';
    public const TRIGGER_TAG_REMOVED = 'tag_removed';
    public const TRIGGER_EMAIL_OPENED = 'email_opened';
    public const TRIGGER_LINK_CLICKED = 'link_clicked';
    public const TRIGGER_DATE_FIELD = 'date_field';
    public const TRIGGER_INACTIVITY = 'inactivity';
    public const TRIGGER_WEBHOOK = 'webhook';
    public const TRIGGER_MANUAL = 'manual';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'status',
        'total_enrolled',
        'currently_active',
        'completed',
        'exited',
        'allow_reentry',
        'reentry_delay_days',
        'exit_on_goal',
        'goal_config',
        'schedule',
        'timezone',
        'created_by',
        'activated_at',
        'paused_at',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'goal_config' => 'array',
        'schedule' => 'array',
        'allow_reentry' => 'boolean',
        'exit_on_goal' => 'boolean',
        'activated_at' => 'datetime',
        'paused_at' => 'datetime',
    ];

    protected $appends = [
        'trigger_label',
        'status_label',
    ];

    /**
     * Get available trigger types.
     */
    public static function getTriggerTypes(): array
    {
        return [
            self::TRIGGER_LIST_SUBSCRIPTION => 'Inscription à une liste',
            self::TRIGGER_TAG_ADDED => 'Tag ajouté',
            self::TRIGGER_TAG_REMOVED => 'Tag retiré',
            self::TRIGGER_EMAIL_OPENED => 'Email ouvert',
            self::TRIGGER_LINK_CLICKED => 'Lien cliqué',
            self::TRIGGER_DATE_FIELD => 'Date (anniversaire, etc.)',
            self::TRIGGER_INACTIVITY => 'Inactivité',
            self::TRIGGER_WEBHOOK => 'Webhook externe',
            self::TRIGGER_MANUAL => 'Ajout manuel',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_PAUSED => 'En pause',
            self::STATUS_ARCHIVED => 'Archivé',
        ];
    }

    /**
     * Get trigger label attribute.
     */
    public function getTriggerLabelAttribute(): string
    {
        return self::getTriggerTypes()[$this->trigger_type] ?? $this->trigger_type;
    }

    /**
     * Get status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Tenant relationship.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Creator relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Steps relationship.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(AutomationStep::class)->orderBy('position');
    }

    /**
     * Root steps (no parent).
     */
    public function rootSteps(): HasMany
    {
        return $this->hasMany(AutomationStep::class)
            ->whereNull('parent_step_id')
            ->orderBy('position');
    }

    /**
     * Enrollments relationship.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(AutomationEnrollment::class);
    }

    /**
     * Active enrollments.
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(AutomationEnrollment::class)
            ->whereIn('status', ['active', 'waiting']);
    }

    /**
     * Logs relationship.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class);
    }

    /**
     * Check if automation is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if automation is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if automation can be activated.
     */
    public function canBeActivated(): bool
    {
        // Must have at least one step
        return $this->steps()->count() > 0;
    }

    /**
     * Activate the automation.
     */
    public function activate(): bool
    {
        if (!$this->canBeActivated()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'activated_at' => now(),
            'paused_at' => null,
        ]);

        return true;
    }

    /**
     * Pause the automation.
     */
    public function pause(): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
            'paused_at' => now(),
        ]);
    }

    /**
     * Archive the automation.
     */
    public function archive(): void
    {
        // Exit all active enrollments
        $this->activeEnrollments()->update([
            'status' => 'exited',
            'exit_reason' => 'automation_archived',
            'exited_at' => now(),
        ]);

        $this->update([
            'status' => self::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Check if a contact can be enrolled.
     */
    public function canEnrollContact(Contact $contact): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check if already enrolled
        $existingEnrollment = $this->enrollments()
            ->where('contact_id', $contact->id)
            ->whereIn('status', ['active', 'waiting'])
            ->exists();

        if ($existingEnrollment) {
            return false;
        }

        // Check reentry rules
        if (!$this->allow_reentry) {
            $hasCompleted = $this->enrollments()
                ->where('contact_id', $contact->id)
                ->where('status', 'completed')
                ->exists();

            if ($hasCompleted) {
                return false;
            }
        } elseif ($this->reentry_delay_days) {
            $lastEnrollment = $this->enrollments()
                ->where('contact_id', $contact->id)
                ->latest('enrolled_at')
                ->first();

            if ($lastEnrollment && $lastEnrollment->enrolled_at->addDays($this->reentry_delay_days)->isFuture()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enroll a contact.
     */
    public function enrollContact(Contact $contact, array $metadata = []): ?AutomationEnrollment
    {
        if (!$this->canEnrollContact($contact)) {
            return null;
        }

        $firstStep = $this->rootSteps()->first();

        $enrollment = $this->enrollments()->create([
            'contact_id' => $contact->id,
            'current_step_id' => $firstStep?->id,
            'status' => $firstStep ? 'active' : 'completed',
            'enrolled_at' => now(),
            'next_action_at' => now(),
            'metadata' => $metadata,
            'step_history' => [],
        ]);

        // Update statistics
        $this->increment('total_enrolled');
        if ($firstStep) {
            $this->increment('currently_active');
        } else {
            $this->increment('completed');
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return $enrollment;
    }

    /**
     * Increment statistics.
     */
    public function incrementStat(string $stat, int $amount = 1): void
    {
        if (in_array($stat, ['total_enrolled', 'currently_active', 'completed', 'exited'])) {
            $this->increment($stat, $amount);
        }
    }

    /**
     * Decrement statistics.
     */
    public function decrementStat(string $stat, int $amount = 1): void
    {
        if (in_array($stat, ['currently_active'])) {
            $this->decrement($stat, $amount);
        }
    }

    /**
     * Get conversion rate.
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->total_enrolled === 0) {
            return 0;
        }

        return round(($this->completed / $this->total_enrolled) * 100, 2);
    }

    /**
     * Get exit rate.
     */
    public function getExitRateAttribute(): float
    {
        if ($this->total_enrolled === 0) {
            return 0;
        }

        return round(($this->exited / $this->total_enrolled) * 100, 2);
    }

    /**
     * Scope for active automations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for specific trigger type.
     */
    public function scopeWithTrigger($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Get the workflow structure for the builder.
     */
    public function getWorkflowStructure(): array
    {
        $steps = $this->steps()->with('children')->get()->keyBy('id');

        $buildTree = function ($parentId = null) use ($steps, &$buildTree) {
            $children = $steps->filter(fn ($step) => $step->parent_step_id === $parentId);

            return $children->map(function ($step) use ($buildTree) {
                return [
                    'id' => $step->id,
                    'type' => $step->type,
                    'name' => $step->name,
                    'config' => $step->config,
                    'position' => $step->position,
                    'branch' => $step->branch,
                    'stats' => [
                        'entered' => $step->entered_count,
                        'completed' => $step->completed_count,
                        'failed' => $step->failed_count,
                    ],
                    'children' => $buildTree($step->id)->values()->toArray(),
                ];
            })->values()->toArray();
        };

        return $buildTree(null);
    }
}
