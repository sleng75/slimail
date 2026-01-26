<?php

namespace App\Listeners;

use App\Events\ContactSubscribedToList;
use App\Jobs\TriggerAutomationJob;
use App\Models\Automation;

class TriggerListSubscriptionAutomation
{
    /**
     * Handle the event.
     */
    public function handle(ContactSubscribedToList $event): void
    {
        TriggerAutomationJob::dispatch(
            Automation::TRIGGER_LIST_SUBSCRIPTION,
            $event->contact,
            ['list_id' => $event->list->id, 'list_name' => $event->list->name]
        );
    }
}
