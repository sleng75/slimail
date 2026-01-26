<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationEnrollment extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXITED = 'exited';
    public const STATUS_FAILED = 'failed';

    // Exit reasons
    public const EXIT_GOAL_REACHED = 'goal_reached';
    public const EXIT_MANUAL = 'manual';
    public const EXIT_UNSUBSCRIBED = 'unsubscribed';
    public const EXIT_ERROR = 'error';
    public const EXIT_AUTOMATION_PAUSED = 'automation_paused';
    public const EXIT_AUTOMATION_ARCHIVED = 'automation_archived';
    public const EXIT_STEP = 'exit_step';

    protected $fillable = [
        'automation_id',
        'contact_id',
        'current_step_id',
        'status',
        'exit_reason',
        'enrolled_at',
        'next_action_at',
        'completed_at',
        'exited_at',
        'step_history',
        'metadata',
        'wait_until',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'next_action_at' => 'datetime',
        'completed_at' => 'datetime',
        'exited_at' => 'datetime',
        'step_history' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_label',
        'duration',
    ];

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_WAITING => 'En attente',
            self::STATUS_COMPLETED => 'Terminé',
            self::STATUS_EXITED => 'Sorti',
            self::STATUS_FAILED => 'Échoué',
        ];
    }

    /**
     * Get exit reasons.
     */
    public static function getExitReasons(): array
    {
        return [
            self::EXIT_GOAL_REACHED => 'Objectif atteint',
            self::EXIT_MANUAL => 'Retiré manuellement',
            self::EXIT_UNSUBSCRIBED => 'Désabonné',
            self::EXIT_ERROR => 'Erreur',
            self::EXIT_AUTOMATION_PAUSED => 'Automatisation en pause',
            self::EXIT_AUTOMATION_ARCHIVED => 'Automatisation archivée',
            self::EXIT_STEP => 'Étape de sortie',
        ];
    }

    /**
     * Get status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Get exit reason label.
     */
    public function getExitReasonLabelAttribute(): ?string
    {
        if (!$this->exit_reason) {
            return null;
        }
        return self::getExitReasons()[$this->exit_reason] ?? $this->exit_reason;
    }

    /**
     * Get duration in the automation.
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->enrolled_at) {
            return null;
        }

        $endDate = $this->completed_at ?? $this->exited_at ?? now();
        $diff = $this->enrolled_at->diff($endDate);

        if ($diff->days > 0) {
            return $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
        }

        if ($diff->h > 0) {
            return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        }

        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }

    /**
     * Automation relationship.
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    /**
     * Contact relationship.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Current step relationship.
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(AutomationStep::class, 'current_step_id');
    }

    /**
     * Logs relationship.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class, 'enrollment_id');
    }

    /**
     * Check if enrollment is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_WAITING]);
    }

    /**
     * Check if enrollment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if enrollment has exited.
     */
    public function hasExited(): bool
    {
        return $this->status === self::STATUS_EXITED;
    }

    /**
     * Check if ready for next action.
     */
    public function isReadyForAction(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->status === self::STATUS_WAITING) {
            return $this->next_action_at && $this->next_action_at->isPast();
        }

        return true;
    }

    /**
     * Move to next step.
     */
    public function moveToStep(?AutomationStep $step): void
    {
        // Record current step in history
        if ($this->current_step_id) {
            $history = $this->step_history ?? [];
            $history[] = [
                'step_id' => $this->current_step_id,
                'completed_at' => now()->toIso8601String(),
            ];
            $this->step_history = $history;
        }

        if (!$step) {
            $this->complete();
            return;
        }

        $this->update([
            'current_step_id' => $step->id,
            'status' => self::STATUS_ACTIVE,
            'next_action_at' => now(),
        ]);

        // Increment step entered count
        $step->incrementStat('entered_count');
    }

    /**
     * Set waiting state.
     */
    public function setWaiting(int $seconds): void
    {
        $this->update([
            'status' => self::STATUS_WAITING,
            'next_action_at' => now()->addSeconds($seconds),
            'wait_until' => now()->addSeconds($seconds)->timestamp,
        ]);
    }

    /**
     * Complete the enrollment.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'current_step_id' => null,
        ]);

        // Update automation statistics
        $this->automation->decrementStat('currently_active');
        $this->automation->incrementStat('completed');
    }

    /**
     * Exit the enrollment.
     */
    public function exit(string $reason = self::EXIT_MANUAL): void
    {
        $this->update([
            'status' => self::STATUS_EXITED,
            'exit_reason' => $reason,
            'exited_at' => now(),
            'current_step_id' => null,
        ]);

        // Update automation statistics
        $this->automation->decrementStat('currently_active');
        $this->automation->incrementStat('exited');
    }

    /**
     * Mark as failed.
     */
    public function fail(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'exit_reason' => $reason ?? self::EXIT_ERROR,
            'exited_at' => now(),
        ]);

        // Update automation statistics
        $this->automation->decrementStat('currently_active');
        $this->automation->incrementStat('exited');
    }

    /**
     * Log an action.
     */
    public function log(string $action, string $status = 'success', ?string $message = null, array $data = []): AutomationLog
    {
        return $this->logs()->create([
            'automation_id' => $this->automation_id,
            'step_id' => $this->current_step_id,
            'contact_id' => $this->contact_id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Get the step history with details.
     */
    public function getDetailedHistoryAttribute(): array
    {
        $history = $this->step_history ?? [];
        $stepIds = collect($history)->pluck('step_id')->toArray();
        $steps = AutomationStep::whereIn('id', $stepIds)->get()->keyBy('id');

        return collect($history)->map(function ($item) use ($steps) {
            $step = $steps[$item['step_id']] ?? null;
            return [
                'step_id' => $item['step_id'],
                'step_type' => $step?->type,
                'step_name' => $step?->name ?? $step?->type_label,
                'completed_at' => $item['completed_at'],
            ];
        })->toArray();
    }

    /**
     * Scope for active enrollments.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_WAITING]);
    }

    /**
     * Scope for enrollments ready for processing.
     */
    public function scopeReadyForProcessing($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_ACTIVE)
                ->orWhere(function ($q2) {
                    $q2->where('status', self::STATUS_WAITING)
                        ->where('next_action_at', '<=', now());
                });
        });
    }
}
