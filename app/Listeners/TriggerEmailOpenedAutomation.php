<?php

namespace App\Listeners;

use App\Events\EmailOpened;
use App\Jobs\TriggerAutomationJob;
use App\Models\Automation;

class TriggerEmailOpenedAutomation
{
    /**
     * Handle the event.
     */
    public function handle(EmailOpened $event): void
    {
        TriggerAutomationJob::dispatch(
            Automation::TRIGGER_EMAIL_OPENED,
            $event->contact,
            [
                'sent_email_id' => $event->sentEmail->id,
                'campaign_id' => $event->sentEmail->campaign_id,
            ]
        );
    }
}
