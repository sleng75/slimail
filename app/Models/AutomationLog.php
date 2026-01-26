<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    use HasFactory;

    // Disable updated_at
    public const UPDATED_AT = null;

    // Action types
    public const ACTION_ENROLLED = 'enrolled';
    public const ACTION_STEP_STARTED = 'step_started';
    public const ACTION_STEP_COMPLETED = 'step_completed';
    public const ACTION_STEP_FAILED = 'step_failed';
    public const ACTION_CONDITION_EVALUATED = 'condition_evaluated';
    public const ACTION_EMAIL_SENT = 'email_sent';
    public const ACTION_EMAIL_OPENED = 'email_opened';
    public const ACTION_EMAIL_CLICKED = 'email_clicked';
    public const ACTION_WAIT_STARTED = 'wait_started';
    public const ACTION_WAIT_COMPLETED = 'wait_completed';
    public const ACTION_TAG_ADDED = 'tag_added';
    public const ACTION_TAG_REMOVED = 'tag_removed';
    public const ACTION_LIST_ADDED = 'list_added';
    public const ACTION_LIST_REMOVED = 'list_removed';
    public const ACTION_FIELD_UPDATED = 'field_updated';
    public const ACTION_WEBHOOK_CALLED = 'webhook_called';
    public const ACTION_GOAL_REACHED = 'goal_reached';
    public const ACTION_EXITED = 'exited';
    public const ACTION_COMPLETED = 'completed';
    public const ACTION_FAILED = 'failed';

    protected $fillable = [
        'automation_id',
        'enrollment_id',
        'step_id',
        'contact_id',
        'action',
        'status',
        'message',
        'data',
        'sent_email_id',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'action_label',
    ];

    /**
     * Get action labels.
     */
    public static function getActionLabels(): array
    {
        return [
            self::ACTION_ENROLLED => 'Inscrit à l\'automatisation',
            self::ACTION_STEP_STARTED => 'Étape démarrée',
            self::ACTION_STEP_COMPLETED => 'Étape terminée',
            self::ACTION_STEP_FAILED => 'Étape échouée',
            self::ACTION_CONDITION_EVALUATED => 'Condition évaluée',
            self::ACTION_EMAIL_SENT => 'Email envoyé',
            self::ACTION_EMAIL_OPENED => 'Email ouvert',
            self::ACTION_EMAIL_CLICKED => 'Lien cliqué',
            self::ACTION_WAIT_STARTED => 'Attente démarrée',
            self::ACTION_WAIT_COMPLETED => 'Attente terminée',
            self::ACTION_TAG_ADDED => 'Tag ajouté',
            self::ACTION_TAG_REMOVED => 'Tag retiré',
            self::ACTION_LIST_ADDED => 'Ajouté à la liste',
            self::ACTION_LIST_REMOVED => 'Retiré de la liste',
            self::ACTION_FIELD_UPDATED => 'Champ mis à jour',
            self::ACTION_WEBHOOK_CALLED => 'Webhook appelé',
            self::ACTION_GOAL_REACHED => 'Objectif atteint',
            self::ACTION_EXITED => 'Sorti de l\'automatisation',
            self::ACTION_COMPLETED => 'Automatisation terminée',
            self::ACTION_FAILED => 'Erreur',
        ];
    }

    /**
     * Get action label attribute.
     */
    public function getActionLabelAttribute(): string
    {
        return self::getActionLabels()[$this->action] ?? $this->action;
    }

    /**
     * Automation relationship.
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    /**
     * Enrollment relationship.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AutomationEnrollment::class, 'enrollment_id');
    }

    /**
     * Step relationship.
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(AutomationStep::class, 'step_id');
    }

    /**
     * Contact relationship.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Sent email relationship.
     */
    public function sentEmail(): BelongsTo
    {
        return $this->belongsTo(SentEmail::class);
    }

    /**
     * Check if action was successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if action failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope for specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for successful actions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed actions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
