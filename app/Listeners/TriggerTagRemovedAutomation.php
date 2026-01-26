<?php

namespace App\Listeners;

use App\Events\ContactTagRemoved;
use App\Jobs\TriggerAutomationJob;
use App\Models\Automation;

class TriggerTagRemovedAutomation
{
    /**
     * Handle the event.
     */
    public function handle(ContactTagRemoved $event): void
    {
        TriggerAutomationJob::dispatch(
            Automation::TRIGGER_TAG_REMOVED,
            $event->contact,
            ['tag_id' => $event->tag->id, 'tag_name' => $event->tag->name]
        );
    }
}
