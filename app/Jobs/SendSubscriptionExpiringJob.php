<?php

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionExpiringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Subscription $subscription,
        public int $daysRemaining
    ) {}

    public function handle(): void
    {
        $subscription = $this->subscription;
        $tenant = $subscription->tenant;

        if (!$tenant || !$tenant->email) {
            return;
        }

        // Don't send if subscription is already canceled or expired
        if ($subscription->isCanceled() || $subscription->isExpired()) {
            return;
        }

        $urgency = $this->daysRemaining <= 3 ? 'Urgent : ' : '';

        Mail::send('emails.subscription-expiring', [
            'subscription' => $subscription,
            'tenant' => $tenant,
            'daysRemaining' => $this->daysRemaining,
        ], function ($message) use ($tenant, $urgency) {
            $message->to($tenant->email, $tenant->name)
                ->subject("{$urgency}Votre abonnement SliMail expire bientôt");
        });
    }

    public function tags(): array
    {
        return ['email', 'subscription', "subscription:{$this->subscription->id}"];
    }
}
