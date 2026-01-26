<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TriggerAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The contact ID.
     */
    protected int $contactId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $triggerType,
        Contact $contact,
        public array $triggerData = []
    ) {
        $this->contactId = $contact->id;
        $this->onQueue('automations');
    }

    /**
     * Execute the job.
     */
    public function handle(AutomationService $automationService): void
    {
        $contact = Contact::find($this->contactId);

        if (!$contact) {
            Log::warning('TriggerAutomationJob: Contact not found', [
                'contact_id' => $this->contactId,
            ]);
            return;
        }

        $enrolled = $automationService->triggerAutomation(
            $this->triggerType,
            $contact,
            $this->triggerData
        );

        if ($enrolled > 0) {
            Log::info('Automation triggered', [
                'trigger_type' => $this->triggerType,
                'contact_id' => $this->contactId,
                'enrollments' => $enrolled,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TriggerAutomationJob failed', [
            'trigger_type' => $this->triggerType,
            'contact_id' => $this->contactId,
            'error' => $exception->getMessage(),
        ]);
    }
}
