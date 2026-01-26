<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceEmailJob;
use App\Models\Invoice;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'billing:send-reminders';
    protected $description = 'Send payment reminders for pending and overdue invoices';

    public function handle(): int
    {
        $this->info('Checking for invoices requiring reminders...');

        // Get pending invoices approaching due date (3 days before)
        $pendingInvoices = Invoice::pending()
            ->where('due_date', '<=', now()->addDays(3))
            ->where('due_date', '>', now())
            ->where(function ($query) {
                $query->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<', now()->subDays(2));
            })
            ->get();

        foreach ($pendingInvoices as $invoice) {
            SendInvoiceEmailJob::dispatch($invoice, 'reminder');
            $this->line("  → Reminder sent for invoice {$invoice->number}");
        }

        // Get overdue invoices
        $overdueInvoices = Invoice::pending()
            ->where('due_date', '<', now())
            ->where(function ($query) {
                $query->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<', now()->subDays(3));
            })
            ->where('reminder_count', '<', 5) // Max 5 reminders
            ->get();

        foreach ($overdueInvoices as $invoice) {
            // Update status to overdue
            $invoice->update(['status' => Invoice::STATUS_OVERDUE]);

            SendInvoiceEmailJob::dispatch($invoice, 'overdue');
            $this->line("  → Overdue reminder sent for invoice {$invoice->number}");
        }

        $totalReminders = $pendingInvoices->count() + $overdueInvoices->count();
        $this->info("Sent {$totalReminders} payment reminders.");

        return Command::SUCCESS;
    }
}
