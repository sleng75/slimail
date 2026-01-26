<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:process
                            {--check-expiring : Check for expiring subscriptions}
                            {--expire : Mark expired subscriptions}
                            {--send-reminders : Send payment reminders}
                            {--reset-usage : Reset monthly usage counters}';

    /**
     * The console command description.
     */
    protected $description = 'Process subscription-related tasks (expiry, reminders, usage reset)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing subscriptions...');

        if ($this->option('check-expiring') || !$this->hasAnyOption()) {
            $this->checkExpiringSubscriptions();
        }

        if ($this->option('expire') || !$this->hasAnyOption()) {
            $this->expireSubscriptions();
        }

        if ($this->option('send-reminders') || !$this->hasAnyOption()) {
            $this->sendPaymentReminders();
        }

        if ($this->option('reset-usage')) {
            $this->resetMonthlyUsage();
        }

        $this->info('Subscription processing completed.');

        return Command::SUCCESS;
    }

    /**
     * Check if any option was specified.
     */
    protected function hasAnyOption(): bool
    {
        return $this->option('check-expiring')
            || $this->option('expire')
            || $this->option('send-reminders')
            || $this->option('reset-usage');
    }

    /**
     * Check for subscriptions expiring soon.
     */
    protected function checkExpiringSubscriptions(): void
    {
        $this->info('Checking expiring subscriptions...');

        // Subscriptions expiring in 7 days
        $expiringSoon = Subscription::expiringSoon(7)->with('tenant', 'plan')->get();

        foreach ($expiringSoon as $subscription) {
            $this->line("  - {$subscription->tenant->name}: expires on {$subscription->ends_at->format('d/m/Y')}");

            // TODO: Send email notification
            Log::info('Subscription expiring soon', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'ends_at' => $subscription->ends_at,
            ]);
        }

        $this->info("Found {$expiringSoon->count()} subscriptions expiring within 7 days.");
    }

    /**
     * Mark expired subscriptions.
     */
    protected function expireSubscriptions(): void
    {
        $this->info('Processing expired subscriptions...');

        $expired = Subscription::expired()->get();
        $count = 0;

        foreach ($expired as $subscription) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
            $count++;

            $tenantName = $subscription->tenant?->name ?? 'Unknown';
            $this->line("  - Expired: {$tenantName}");

            Log::info('Subscription marked as expired', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
            ]);
        }

        $this->info("Marked {$count} subscriptions as expired.");
    }

    /**
     * Send payment reminders for overdue invoices.
     */
    protected function sendPaymentReminders(): void
    {
        $this->info('Sending payment reminders...');

        // Update overdue invoices
        Invoice::where('status', Invoice::STATUS_PENDING)
            ->where('due_date', '<', now())
            ->update(['status' => Invoice::STATUS_OVERDUE]);

        // Get invoices that need reminders (not reminded in last 3 days)
        $invoices = Invoice::whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_OVERDUE])
            ->where(function ($query) {
                $query->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<', now()->subDays(3));
            })
            ->where('reminder_count', '<', 3) // Max 3 reminders
            ->with('tenant')
            ->get();

        $count = 0;

        foreach ($invoices as $invoice) {
            // TODO: Send reminder email
            $invoice->recordReminderSent();
            $count++;

            $this->line("  - Reminder sent for invoice {$invoice->number} to {$invoice->billing_email}");

            Log::info('Payment reminder sent', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'reminder_count' => $invoice->reminder_count,
            ]);
        }

        $this->info("Sent {$count} payment reminders.");
    }

    /**
     * Reset monthly usage counters (run on 1st of each month).
     */
    protected function resetMonthlyUsage(): void
    {
        $this->info('Resetting monthly usage counters...');

        $subscriptions = Subscription::whereIn('status', [
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_TRIALING,
        ])->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            $subscription->resetMonthlyUsage();
            $count++;
        }

        $this->info("Reset usage for {$count} subscriptions.");
    }
}
