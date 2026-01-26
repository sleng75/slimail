<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Invoice $invoice,
        public string $type = 'created'
    ) {}

    public function handle(): void
    {
        $invoice = $this->invoice;
        $tenant = $invoice->tenant;

        if (!$tenant || !$tenant->email) {
            return;
        }

        $subject = match($this->type) {
            'created' => "Facture {$invoice->number} - SliMail",
            'reminder' => "Rappel : Facture {$invoice->number} en attente",
            'overdue' => "Urgent : Facture {$invoice->number} en retard",
            default => "Facture {$invoice->number} - SliMail",
        };

        $template = match($this->type) {
            'reminder', 'overdue' => 'emails.payment-reminder',
            default => 'emails.invoice-created',
        };

        Mail::send($template, [
            'invoice' => $invoice,
            'tenant' => $tenant,
        ], function ($message) use ($tenant, $subject) {
            $message->to($tenant->email, $tenant->name)
                ->subject($subject);
        });

        // Mark invoice as sent if it's a new invoice
        if ($this->type === 'created' && !$invoice->sent_at) {
            $invoice->markAsSent();
        }

        // Record reminder if it's a reminder email
        if (in_array($this->type, ['reminder', 'overdue'])) {
            $invoice->recordReminderSent();
        }
    }

    public function tags(): array
    {
        return ['email', 'invoice', "invoice:{$this->invoice->id}"];
    }
}
