<?php

namespace App\Providers;

use App\Events\ContactSubscribedToList;
use App\Events\ContactTagAdded;
use App\Events\ContactTagRemoved;
use App\Events\EmailLinkClicked;
use App\Events\EmailOpened;
use App\Listeners\TriggerEmailOpenedAutomation;
use App\Listeners\TriggerLinkClickedAutomation;
use App\Listeners\TriggerListSubscriptionAutomation;
use App\Listeners\TriggerTagAddedAutomation;
use App\Listeners\TriggerTagRemovedAutomation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ContactSubscribedToList::class => [
            TriggerListSubscriptionAutomation::class,
        ],
        ContactTagAdded::class => [
            TriggerTagAddedAutomation::class,
        ],
        ContactTagRemoved::class => [
            TriggerTagRemovedAutomation::class,
        ],
        EmailOpened::class => [
            TriggerEmailOpenedAutomation::class,
        ],
        EmailLinkClicked::class => [
            TriggerLinkClickedAutomation::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
