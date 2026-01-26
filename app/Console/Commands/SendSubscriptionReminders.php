<?php

namespace App\Console\Commands;

use App\Jobs\SendSubscriptionExpiringJob;
use App\Models\Subscription;
use Illuminate\Console\Command;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:send-expiring-reminders';
    protected $description = 'Send reminders for subscriptions that are about to expire';

    public function handle(): int
    {
        $this->info('Checking for expiring subscriptions...');

        // Days before expiration to send reminders
        $reminderDays = [7, 3, 1];
        $sentCount = 0;

        foreach ($reminderDays as $days) {
            $subscriptions = Subscription::active()
                ->whereNotNull('ends_at')
                ->whereDate('ends_at', now()->addDays($days)->toDateString())
                ->with('tenant', 'plan')
                ->get();

            foreach ($subscriptions as $subscription) {
                if ($subscription->tenant && $subscription->tenant->email) {
                    SendSubscriptionExpiringJob::dispatch($subscription, $days);
                    $this->line("  → Reminder sent for tenant {$subscription->tenant->name} ({$days} days remaining)");
                    $sentCount++;
                }
            }
        }

        $this->info("Sent {$sentCount} subscription expiring reminders.");

        return Command::SUCCESS;
    }
}
