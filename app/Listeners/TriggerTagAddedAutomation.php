<?php

namespace App\Listeners;

use App\Events\ContactTagAdded;
use App\Jobs\TriggerAutomationJob;
use App\Models\Automation;

class TriggerTagAddedAutomation
{
    /**
     * Handle the event.
     */
    public function handle(ContactTagAdded $event): void
    {
        TriggerAutomationJob::dispatch(
            Automation::TRIGGER_TAG_ADDED,
            $event->contact,
            ['tag_id' => $event->tag->id, 'tag_name' => $event->tag->name]
        );
    }
}
