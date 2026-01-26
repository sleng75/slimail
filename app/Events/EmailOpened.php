<?php

namespace App\Events;

use App\Models\Contact;
use App\Models\SentEmail;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailOpened
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Contact $contact,
        public SentEmail $sentEmail
    ) {}
}
