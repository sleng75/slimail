<?php

namespace App\Listeners;

use App\Events\EmailLinkClicked;
use App\Jobs\TriggerAutomationJob;
use App\Models\Automation;

class TriggerLinkClickedAutomation
{
    /**
     * Handle the event.
     */
    public function handle(EmailLinkClicked $event): void
    {
        TriggerAutomationJob::dispatch(
            Automation::TRIGGER_LINK_CLICKED,
            $event->contact,
            [
                'sent_email_id' => $event->sentEmail->id,
                'campaign_id' => $event->sentEmail->campaign_id,
                'url' => $event->url,
            ]
        );
    }
}
