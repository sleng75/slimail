<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Payment $payment
    ) {}

    public function handle(): void
    {
        $payment = $this->payment;
        $tenant = $payment->tenant;
        $invoice = $payment->invoice;
        $subscription = $payment->subscription;

        if (!$tenant || !$tenant->email) {
            return;
        }

        Mail::send('emails.payment-confirmed', [
            'payment' => $payment,
            'tenant' => $tenant,
            'invoice' => $invoice,
            'subscription' => $subscription,
        ], function ($message) use ($tenant, $payment) {
            $message->to($tenant->email, $tenant->name)
                ->subject("Paiement confirmé - {$payment->getFormattedAmount()}");
        });
    }

    public function tags(): array
    {
        return ['email', 'payment', "payment:{$this->payment->id}"];
    }
}
