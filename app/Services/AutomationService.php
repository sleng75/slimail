<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationEnrollment;
use App\Models\AutomationLog;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Tag;
use App\Contracts\EmailServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    public function __construct(
        protected EmailServiceInterface $emailService
    ) {}

    /**
     * Process all pending enrollments.
     */
    public function processEnrollments(): int
    {
        $processed = 0;

        // Get all enrollments ready for processing
        $enrollments = AutomationEnrollment::readyForProcessing()
            ->with(['automation', 'currentStep', 'contact'])
            ->whereHas('automation', fn ($q) => $q->where('status', Automation::STATUS_ACTIVE))
            ->limit(100)
            ->get();

        foreach ($enrollments as $enrollment) {
            try {
                $this->processEnrollment($enrollment);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Automation enrollment processing failed', [
                    'enrollment_id' => $enrollment->id,
                    'error' => $e->getMessage(),
                ]);

                $enrollment->log(
                    AutomationLog::ACTION_FAILED,
                    'failed',
                    $e->getMessage()
                );
            }
        }

        return $processed;
    }

    /**
     * Process a single enrollment.
     */
    public function processEnrollment(AutomationEnrollment $enrollment): void
    {
        if (!$enrollment->isReadyForAction()) {
            return;
        }

        $step = $enrollment->currentStep;

        if (!$step) {
            $enrollment->complete();
            return;
        }

        // Log step started
        $enrollment->log(AutomationLog::ACTION_STEP_STARTED, 'success', null, [
            'step_type' => $step->type,
            'step_name' => $step->name,
        ]);

        // Execute the step
        $result = $this->executeStep($enrollment, $step);

        if ($result['success']) {
            // Mark step as completed
            $step->incrementStat('completed_count');

            $enrollment->log(AutomationLog::ACTION_STEP_COMPLETED, 'success', null, [
                'step_type' => $step->type,
            ]);

            // Move to next step (unless enrollment was already exited)
            $enrollment->refresh();
            if ($enrollment->status !== AutomationEnrollment::STATUS_EXITED) {
                $nextStep = $result['next_step'] ?? $step->getNextStep($result['branch'] ?? null);
                $enrollment->moveToStep($nextStep);
            }
        } else {
            // Step failed
            $step->incrementStat('failed_count');

            $enrollment->log(AutomationLog::ACTION_STEP_FAILED, 'failed', $result['error'] ?? 'Unknown error');

            if ($result['retry'] ?? false) {
                // Schedule retry
                $enrollment->setWaiting(300); // Retry in 5 minutes
            } else {
                $enrollment->fail($result['error'] ?? 'Step execution failed');
            }
        }
    }

    /**
     * Execute a step.
     */
    protected function executeStep(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        return match ($step->type) {
            AutomationStep::TYPE_SEND_EMAIL => $this->executeSendEmail($enrollment, $step),
            AutomationStep::TYPE_WAIT => $this->executeWait($enrollment, $step),
            AutomationStep::TYPE_CONDITION => $this->executeCondition($enrollment, $step),
            AutomationStep::TYPE_ADD_TAG => $this->executeAddTag($enrollment, $step),
            AutomationStep::TYPE_REMOVE_TAG => $this->executeRemoveTag($enrollment, $step),
            AutomationStep::TYPE_ADD_TO_LIST => $this->executeAddToList($enrollment, $step),
            AutomationStep::TYPE_REMOVE_FROM_LIST => $this->executeRemoveFromList($enrollment, $step),
            AutomationStep::TYPE_UPDATE_FIELD => $this->executeUpdateField($enrollment, $step),
            AutomationStep::TYPE_WEBHOOK => $this->executeWebhook($enrollment, $step),
            AutomationStep::TYPE_GOAL => $this->executeGoal($enrollment, $step),
            AutomationStep::TYPE_EXIT => $this->executeExit($enrollment, $step),
            default => ['success' => false, 'error' => 'Unknown step type'],
        };
    }

    /**
     * Execute send email step.
     */
    protected function executeSendEmail(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $config = $step->config;
        $contact = $enrollment->contact;

        // Get template or custom content
        $subject = $config['subject'] ?? 'No Subject';
        $htmlContent = $config['html_content'] ?? '';
        $fromName = $config['from_name'] ?? $enrollment->automation->tenant->name;
        $fromEmail = $config['from_email'] ?? 'noreply@' . config('mail.from.address', 'slimail.com');

        // Replace variables
        $subject = $this->replaceVariables($subject, $contact);
        $htmlContent = $this->replaceVariables($htmlContent, $contact);

        try {
            // Send email via email service
            $result = $this->emailService->sendEmail(
                to: $contact->email,
                subject: $subject,
                htmlBody: $htmlContent,
                fromEmail: $fromEmail,
                fromName: $fromName
            );

            if ($result['success']) {
                $step->incrementStat('emails_sent');

                $enrollment->log(AutomationLog::ACTION_EMAIL_SENT, 'success', null, [
                    'message_id' => $result['message_id'] ?? null,
                    'subject' => $subject,
                ]);

                return ['success' => true];
            }

            return ['success' => false, 'error' => $result['error'] ?? 'Email sending failed', 'retry' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'retry' => true];
        }
    }

    /**
     * Execute wait step.
     */
    protected function executeWait(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $seconds = $step->getWaitDurationSeconds();

        if ($seconds > 0) {
            $enrollment->setWaiting($seconds);

            $enrollment->log(AutomationLog::ACTION_WAIT_STARTED, 'success', null, [
                'duration_seconds' => $seconds,
                'resume_at' => now()->addSeconds($seconds)->toIso8601String(),
            ]);

            // Return success but don't move to next step yet
            return ['success' => true, 'next_step' => $step]; // Stay on this step until wait is over
        }

        $enrollment->log(AutomationLog::ACTION_WAIT_COMPLETED, 'success');

        return ['success' => true];
    }

    /**
     * Execute condition step.
     */
    protected function executeCondition(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $contact = $enrollment->contact;
        $result = $step->evaluateCondition($contact);

        $enrollment->log(AutomationLog::ACTION_CONDITION_EVALUATED, 'success', null, [
            'condition_type' => $step->config['condition_type'] ?? 'unknown',
            'result' => $result ? 'yes' : 'no',
        ]);

        return [
            'success' => true,
            'branch' => $result ? 'yes' : 'no',
        ];
    }

    /**
     * Execute add tag step.
     */
    protected function executeAddTag(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $config = $step->config;
        $tagId = $config['tag_id'] ?? null;

        if (!$tagId) {
            return ['success' => false, 'error' => 'No tag specified'];
        }

        $tag = Tag::find($tagId);
        if (!$tag) {
            return ['success' => false, 'error' => 'Tag not found'];
        }

        $contact = $enrollment->contact;
        $contact->tags()->syncWithoutDetaching([$tagId]);

        $enrollment->log(AutomationLog::ACTION_TAG_ADDED, 'success', null, [
            'tag_id' => $tagId,
            'tag_name' => $tag->name,
        ]);

        return ['success' => true];
    }

    /**
     * Execute remove tag step.
     */
    protected function executeRemoveTag(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $config = $step->config;
        $tagId = $config['tag_id'] ?? null;

        if (!$tagId) {
            return ['success' => false, 'error' => 'No tag specified'];
        }

        $tag = Tag::find($tagId);
        $contact = $enrollment->contact;
        $contact->tags()->detach($tagId);

        $enrollment->log(AutomationLog::ACTION_TAG_REMOVED, 'success', null, [
            'tag_id' => $tagId,
            'tag_name' => $tag?->name,
        ]);

        return ['success' => true];
    }

    /**
     * Execute add to list step.
     */
    protected function executeAddToList(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $config = $step->config;
        $listId = $config['list_id'] ?? null;

        if (!$listId) {
            return ['success' => false, 'error' => 'No list specified'];
        }

        $list = ContactList::find($listId);
        if (!$list) {
            return ['success' => false, 'error' => 'List not found'];
        }

        $contact = $enrollment->contact;
        $contact->lists()->syncWithoutDetaching([$listId => ['subscribed_at' => now()]]);

        // Update list count
        $list->updateContactCount();

        $enrollment->log(AutomationLog::ACTION_LIST_ADDED, 'success', null, [
            'list_id' => $listId,
            'list_name' => $list->name,
        ]);

        return ['success' => true];
    }

    /**
     * Execute remove from list step.
     */
    protected function executeRemoveFromList(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $config = $step->config;
        $listId = $config['list_id'] ?? null;

        if (!$listId) {
            return ['success' => false, 'error' => 'No list specified'];
        }

        $list = ContactList::find($listId);
        $contact = $enrollment->contact;
        $contact->lists()->detach($listId);

        // Update list count
        $list?->updateContactCount();

        $enrollment->log(AutomationLog::ACTION_LIST_REMOVED, 'success', null, [
            'list_id' => $listId,
            'list_name' => $list?->name,
        ]);

        return ['success' => true];
    }

    /**
     * Execute update field step.
     */
    protected function executeUpdateField(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $config = $step->config;
        $field = $config['field'] ?? null;
        $value = $config['value'] ?? null;

        if (!$field) {
            return ['success' => false, 'error' => 'No field specified'];
        }

        $contact = $enrollment->contact;

        // Check if it's a standard field or custom field
        $standardFields = ['first_name', 'last_name', 'phone', 'company', 'job_title'];

        if (in_array($field, $standardFields)) {
            $contact->update([$field => $value]);
        } else {
            $contact->setCustomField($field, $value);
            $contact->save();
        }

        $enrollment->log(AutomationLog::ACTION_FIELD_UPDATED, 'success', null, [
            'field' => $field,
            'value' => $value,
        ]);

        return ['success' => true];
    }

    /**
     * Execute webhook step.
     */
    protected function executeWebhook(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $config = $step->config;
        $url = $config['url'] ?? null;
        $method = strtoupper($config['method'] ?? 'POST');
        $headers = $config['headers'] ?? [];

        if (!$url) {
            return ['success' => false, 'error' => 'No URL specified'];
        }

        $contact = $enrollment->contact;

        // Build payload
        $payload = [
            'automation_id' => $enrollment->automation_id,
            'enrollment_id' => $enrollment->id,
            'contact' => [
                'id' => $contact->id,
                'email' => $contact->email,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'custom_fields' => $contact->custom_fields,
            ],
            'step_id' => $step->id,
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $response = match ($method) {
                'GET' => Http::withHeaders($headers)->timeout(30)->get($url, $payload),
                'POST' => Http::withHeaders($headers)->timeout(30)->post($url, $payload),
                'PUT' => Http::withHeaders($headers)->timeout(30)->put($url, $payload),
                default => Http::withHeaders($headers)->timeout(30)->post($url, $payload),
            };

            if ($response->successful()) {
                $enrollment->log(AutomationLog::ACTION_WEBHOOK_CALLED, 'success', null, [
                    'url' => $url,
                    'method' => $method,
                    'status_code' => $response->status(),
                ]);

                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => "Webhook failed with status {$response->status()}",
                'retry' => $response->status() >= 500,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'retry' => true];
        }
    }

    /**
     * Execute goal step.
     */
    protected function executeGoal(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $enrollment->log(AutomationLog::ACTION_GOAL_REACHED, 'success', null, [
            'goal_name' => $step->name ?? 'Goal',
        ]);

        // If exit_on_goal is enabled for the automation, exit
        if ($enrollment->automation->exit_on_goal) {
            $enrollment->exit(AutomationEnrollment::EXIT_GOAL_REACHED);
            return ['success' => true, 'next_step' => null];
        }

        return ['success' => true];
    }

    /**
     * Execute exit step.
     */
    protected function executeExit(AutomationEnrollment $enrollment, AutomationStep $step): array
    {
        $enrollment->log(AutomationLog::ACTION_EXITED, 'success', null, [
            'reason' => 'exit_step',
        ]);

        $enrollment->exit(AutomationEnrollment::EXIT_STEP);

        return ['success' => true, 'next_step' => null];
    }

    /**
     * Replace variables in content.
     */
    protected function replaceVariables(string $content, Contact $contact): string
    {
        $replacements = [
            '{{email}}' => $contact->email,
            '{{first_name}}' => $contact->first_name ?? '',
            '{{last_name}}' => $contact->last_name ?? '',
            '{{full_name}}' => $contact->full_name ?? '',
            '{{company}}' => $contact->company ?? '',
            '{{phone}}' => $contact->phone ?? '',
            '{{job_title}}' => $contact->job_title ?? '',
        ];

        // Add custom fields
        if ($contact->custom_fields) {
            foreach ($contact->custom_fields as $key => $value) {
                $replacements["{{custom.{$key}}}"] = $value ?? '';
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Trigger automation for a specific event.
     */
    public function triggerAutomation(
        string $triggerType,
        Contact $contact,
        array $triggerData = []
    ): int {
        $enrolled = 0;

        // Find matching automations
        $automations = Automation::where('tenant_id', $contact->tenant_id)
            ->where('status', Automation::STATUS_ACTIVE)
            ->where('trigger_type', $triggerType)
            ->get();

        foreach ($automations as $automation) {
            if ($this->matchesTriggerConfig($automation, $triggerData)) {
                $enrollment = $automation->enrollContact($contact, [
                    'trigger_type' => $triggerType,
                    'trigger_data' => $triggerData,
                ]);

                if ($enrollment) {
                    $enrollment->log(AutomationLog::ACTION_ENROLLED, 'success', null, $triggerData);
                    $enrolled++;
                }
            }
        }

        return $enrolled;
    }

    /**
     * Check if trigger data matches automation config.
     */
    protected function matchesTriggerConfig(Automation $automation, array $triggerData): bool
    {
        $config = $automation->trigger_config ?? [];

        switch ($automation->trigger_type) {
            case Automation::TRIGGER_LIST_SUBSCRIPTION:
                return isset($config['list_id']) && ($triggerData['list_id'] ?? null) == $config['list_id'];

            case Automation::TRIGGER_TAG_ADDED:
            case Automation::TRIGGER_TAG_REMOVED:
                return isset($config['tag_id']) && ($triggerData['tag_id'] ?? null) == $config['tag_id'];

            case Automation::TRIGGER_WEBHOOK:
                return true; // Webhooks always trigger

            case Automation::TRIGGER_MANUAL:
                return true; // Manual always triggers when called

            default:
                return true;
        }
    }

    /**
     * Save workflow structure from builder.
     */
    public function saveWorkflow(Automation $automation, array $steps): void
    {
        DB::transaction(function () use ($automation, $steps) {
            // Delete existing steps
            $automation->steps()->delete();

            // Create new steps
            $this->createStepsRecursively($automation, $steps);
        });
    }

    /**
     * Create steps recursively from builder data.
     */
    protected function createStepsRecursively(
        Automation $automation,
        array $steps,
        ?int $parentId = null,
        ?string $branch = null,
        int $startPosition = 0
    ): void {
        foreach ($steps as $index => $stepData) {
            $step = $automation->steps()->create([
                'type' => $stepData['type'],
                'name' => $stepData['name'] ?? null,
                'config' => $stepData['config'] ?? [],
                'position' => $startPosition + $index,
                'parent_step_id' => $parentId,
                'branch' => $branch,
            ]);

            // Handle children (for conditions)
            if (!empty($stepData['yes_branch'])) {
                $this->createStepsRecursively($automation, $stepData['yes_branch'], $step->id, 'yes');
            }

            if (!empty($stepData['no_branch'])) {
                $this->createStepsRecursively($automation, $stepData['no_branch'], $step->id, 'no');
            }

            // Handle linear children
            if (!empty($stepData['children'])) {
                $this->createStepsRecursively($automation, $stepData['children'], $step->id);
            }
        }
    }

    /**
     * Duplicate an automation.
     */
    public function duplicate(Automation $automation): Automation
    {
        return DB::transaction(function () use ($automation) {
            $newAutomation = $automation->replicate([
                'status',
                'total_enrolled',
                'currently_active',
                'completed',
                'exited',
                'activated_at',
                'paused_at',
            ]);

            $newAutomation->name = $automation->name . ' (copie)';
            $newAutomation->status = Automation::STATUS_DRAFT;
            $newAutomation->save();

            // Duplicate steps
            $this->duplicateSteps($automation, $newAutomation);

            return $newAutomation;
        });
    }

    /**
     * Duplicate steps to a new automation.
     */
    protected function duplicateSteps(Automation $source, Automation $target, ?int $sourceParentId = null, ?int $targetParentId = null): void
    {
        $steps = $source->steps()
            ->where('parent_step_id', $sourceParentId)
            ->orderBy('position')
            ->get();

        foreach ($steps as $step) {
            $newStep = $step->replicate([
                'automation_id',
                'parent_step_id',
                'entered_count',
                'completed_count',
                'failed_count',
                'emails_sent',
                'emails_opened',
                'emails_clicked',
            ]);

            $newStep->automation_id = $target->id;
            $newStep->parent_step_id = $targetParentId;
            $newStep->save();

            // Recursively duplicate children
            $this->duplicateSteps($source, $target, $step->id, $newStep->id);
        }
    }
}
