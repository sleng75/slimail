<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payment\CinetPayService;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    protected CinetPayService $cinetPayService;

    public function __construct(CinetPayService $cinetPayService)
    {
        $this->cinetPayService = $cinetPayService;
    }

    /**
     * Display the billing dashboard.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $subscription = $tenant->subscription;

        // Get recent invoices
        $recentInvoices = Invoice::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'status_label' => $invoice->getStatusLabel(),
                'status_color' => $invoice->getStatusColor(),
                'total' => $invoice->getFormattedTotal(),
                'issue_date' => $invoice->issue_date->format('d/m/Y'),
                'due_date' => $invoice->due_date->format('d/m/Y'),
            ]);

        // Get recent payments
        $recentPayments = Payment::where('tenant_id', $tenant->id)
            ->where('is_refund', false)
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($payment) => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->getFormattedAmount(),
                'status' => $payment->status,
                'status_label' => $payment->getStatusLabel(),
                'status_color' => $payment->getStatusColor(),
                'payment_method' => $payment->getPaymentMethodLabel(),
                'date' => $payment->created_at->format('d/m/Y H:i'),
            ]);

        // Subscription data
        $subscriptionData = null;
        if ($subscription) {
            $subscriptionData = [
                'id' => $subscription->id,
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'price' => $subscription->plan->getFormattedPrice($subscription->billing_cycle),
                ],
                'status' => $subscription->status,
                'billing_cycle' => $subscription->billing_cycle,
                'on_trial' => $subscription->onTrial(),
                'trial_days_remaining' => $subscription->trialDaysRemaining(),
                'days_remaining' => $subscription->daysRemaining(),
                'starts_at' => $subscription->starts_at?->format('d/m/Y'),
                'ends_at' => $subscription->ends_at?->format('d/m/Y'),
                'usage' => [
                    'emails' => [
                        'used' => $subscription->emails_used,
                        'limit' => $subscription->plan->emails_per_month,
                        'percent' => round($subscription->emailUsagePercent(), 1),
                    ],
                    'contacts' => [
                        'count' => $subscription->contacts_count,
                        'limit' => $subscription->plan->contacts_limit,
                        'percent' => round($subscription->contactUsagePercent(), 1),
                    ],
                ],
            ];
        }

        return Inertia::render('Billing/Index', [
            'subscription' => $subscriptionData,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'hasUnpaidInvoices' => Invoice::where('tenant_id', $tenant->id)->unpaid()->exists(),
        ]);
    }

    /**
     * Display available plans.
     */
    public function plans(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $currentSubscription = $tenant->subscription;

        $plans = Plan::active()
            ->public()
            ->ordered()
            ->get()
            ->map(fn($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'price_monthly_formatted' => $plan->getFormattedPrice('monthly'),
                'price_yearly_formatted' => $plan->getFormattedPrice('yearly'),
                'monthly_equivalent' => $plan->getMonthlyEquivalent(),
                'yearly_savings' => $plan->getYearlySavingsPercent(),
                'is_free' => $plan->isFree(),
                'is_popular' => $plan->is_popular,
                'trial_days' => $plan->trial_days,
                'limits' => [
                    'emails' => $plan->getLimitText('emails'),
                    'contacts' => $plan->getLimitText('contacts'),
                    'campaigns' => $plan->getLimitText('campaigns'),
                    'templates' => $plan->getLimitText('templates'),
                    'users' => $plan->getLimitText('users'),
                ],
                'features' => $plan->features,
                'color' => $plan->color,
                'is_current' => $currentSubscription && $currentSubscription->plan_id === $plan->id,
            ]);

        return Inertia::render('Billing/Plans', [
            'plans' => $plans,
            'currentPlanId' => $currentSubscription?->plan_id,
            'billingCycle' => $currentSubscription?->billing_cycle ?? 'monthly',
        ]);
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request, Plan $plan)
    {
        $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $tenant = $request->user()->tenant;
        $billingCycle = $request->billing_cycle;
        $price = $plan->getPrice($billingCycle);

        // Check if free plan
        if ($plan->isFree()) {
            return $this->createFreeSubscription($tenant, $plan);
        }

        // Create or update subscription (pending payment)
        $result = DB::transaction(function () use ($tenant, $plan, $billingCycle, $price) {
            // Cancel existing subscription if any
            if ($tenant->subscription) {
                $tenant->subscription->cancel('Upgrade/Change plan');
            }

            // Create new subscription
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'price' => $price,
                'currency' => 'XOF',
                'status' => $plan->trial_days > 0
                    ? Subscription::STATUS_TRIALING
                    : Subscription::STATUS_PAST_DUE, // Will be activated after payment
                'trial_ends_at' => $plan->trial_days > 0
                    ? now()->addDays($plan->trial_days)
                    : null,
                'starts_at' => now(),
                'ends_at' => now()->addDays($billingCycle === 'yearly' ? 365 : 30),
            ]);

            // Create invoice
            $invoice = $this->createInvoice($tenant, $subscription, $plan, $billingCycle, $price);

            return [
                'subscription' => $subscription,
                'invoice' => $invoice,
                'plan' => $plan,
            ];
        });

        // If trial, don't require immediate payment
        if ($plan->trial_days > 0) {
            // Activate subscription for trial
            $result['subscription']->update(['status' => Subscription::STATUS_TRIALING]);

            return redirect()->route('billing.index')
                ->with('success', "Votre période d'essai de {$plan->trial_days} jours a commencé!");
        }

        // Initialize payment
        $paymentResult = $this->cinetPayService->initializePayment(
            $tenant,
            $price,
            "Abonnement {$plan->name} - " . ($billingCycle === 'yearly' ? 'Annuel' : 'Mensuel'),
            $result['invoice'],
            $result['subscription'],
            [
                'name' => $tenant->name,
                'email' => $tenant->email,
            ]
        );

        if ($paymentResult['success']) {
            return Inertia::location($paymentResult['payment_url']);
        }

        return back()->withErrors(['payment' => $paymentResult['error']]);
    }

    /**
     * Create a free subscription.
     */
    protected function createFreeSubscription($tenant, Plan $plan)
    {
        DB::transaction(function () use ($tenant, $plan) {
            if ($tenant->subscription) {
                $tenant->subscription->cancel('Switched to free plan');
            }

            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'price' => 0,
                'currency' => 'XOF',
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'ends_at' => null, // Free plans don't expire
            ]);
        });

        return redirect()->route('billing.index')
            ->with('success', 'Vous utilisez maintenant le forfait gratuit.');
    }

    /**
     * Create invoice for subscription.
     */
    protected function createInvoice($tenant, Subscription $subscription, Plan $plan, string $billingCycle, float $price): Invoice
    {
        $periodLabel = $billingCycle === 'yearly' ? 'Annuel' : 'Mensuel';
        $periodEnd = $billingCycle === 'yearly'
            ? now()->addYear()->format('d/m/Y')
            : now()->addMonth()->format('d/m/Y');

        return Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => $price,
            'tax_rate' => 0, // Adjust based on your tax requirements
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => $price,
            'balance_due' => $price,
            'currency' => 'XOF',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'billing_name' => $tenant->name,
            'billing_email' => $tenant->email,
            'billing_phone' => $tenant->phone ?? null,
            'billing_address' => $tenant->address ?? null,
            'billing_city' => $tenant->city ?? null,
            'billing_country' => $tenant->country ?? 'BF',
            'line_items' => [
                [
                    'description' => "Abonnement {$plan->name} - {$periodLabel} (jusqu'au {$periodEnd})",
                    'quantity' => 1,
                    'unit_price' => $price,
                    'total' => $price,
                ],
            ],
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $tenant = $request->user()->tenant;
        $subscription = $tenant->subscription;

        if (!$subscription) {
            return back()->withErrors(['subscription' => 'Aucun abonnement actif']);
        }

        $subscription->cancel($request->reason, $request->feedback);

        return redirect()->route('billing.index')
            ->with('success', 'Votre abonnement a été annulé. Vous conservez l\'accès jusqu\'à la fin de la période.');
    }

    /**
     * Payment return handler.
     */
    public function paymentReturn(Request $request, Payment $payment)
    {
        // Check payment status
        $result = $this->cinetPayService->checkPaymentStatus($payment);

        if ($result['success'] && $result['status'] === Payment::STATUS_COMPLETED) {
            return redirect()->route('billing.index')
                ->with('success', 'Paiement effectué avec succès! Votre abonnement est maintenant actif.');
        }

        return redirect()->route('billing.index')
            ->with('warning', 'Le statut du paiement est en cours de vérification.');
    }

    /**
     * Display invoices list.
     */
    public function invoices(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn($invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'status_label' => $invoice->getStatusLabel(),
                'status_color' => $invoice->getStatusColor(),
                'total' => $invoice->getFormattedTotal(),
                'balance_due' => $invoice->getFormattedBalanceDue(),
                'issue_date' => $invoice->issue_date->format('d/m/Y'),
                'due_date' => $invoice->due_date->format('d/m/Y'),
                'paid_at' => $invoice->paid_at?->format('d/m/Y'),
                'is_overdue' => $invoice->isOverdue(),
            ]);

        return Inertia::render('Billing/Invoices', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * View a single invoice.
     */
    public function showInvoice(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        // Format line items with formatted prices
        $lineItems = collect($invoice->line_items ?? [])->map(function ($item) use ($invoice) {
            return [
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'total' => $item['total'] ?? 0,
                'unit_price_formatted' => number_format($item['unit_price'] ?? 0, 0, ',', ' ') . ' ' . $invoice->currency,
                'total_formatted' => number_format($item['total'] ?? 0, 0, ',', ' ') . ' ' . $invoice->currency,
            ];
        })->toArray();

        return Inertia::render('Billing/InvoiceDetail', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'status_label' => $invoice->getStatusLabel(),
                'status_color' => $invoice->getStatusColor(),
                'subtotal' => number_format($invoice->subtotal, 0, ',', ' ') . ' ' . $invoice->currency,
                'tax_rate' => $invoice->tax_rate,
                'tax' => number_format($invoice->tax_amount, 0, ',', ' ') . ' ' . $invoice->currency,
                'discount' => number_format($invoice->discount_amount, 0, ',', ' ') . ' ' . $invoice->currency,
                'discount_amount' => $invoice->discount_amount,
                'tax_amount' => $invoice->tax_amount,
                'total' => $invoice->getFormattedTotal(),
                'paid' => number_format($invoice->amount_paid, 0, ',', ' ') . ' ' . $invoice->currency,
                'due' => $invoice->getFormattedBalanceDue(),
                'amount_paid' => $invoice->amount_paid,
                'amount_due' => $invoice->balance_due,
                'currency' => $invoice->currency,
                'issue_date' => $invoice->issue_date->format('d/m/Y'),
                'due_date' => $invoice->due_date->format('d/m/Y'),
                'paid_at' => $invoice->paid_at?->format('d/m/Y H:i'),
                'billing_name' => $invoice->billing_name,
                'billing_email' => $invoice->billing_email,
                'billing_phone' => $invoice->billing_phone,
                'billing_address' => $invoice->billing_address,
                'billing_city' => $invoice->billing_city,
                'billing_country' => $invoice->billing_country,
                'billing_tax_id' => $invoice->billing_tax_id,
                'line_items' => $lineItems,
                'notes' => $invoice->notes,
                'is_overdue' => $invoice->isOverdue(),
                'can_pay' => !$invoice->isPaid() && $invoice->balance_due > 0,
            ],
        ]);
    }

    /**
     * Pay an invoice.
     */
    public function payInvoice(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        if ($invoice->isPaid()) {
            return back()->withErrors(['invoice' => 'Cette facture est déjà payée']);
        }

        $tenant = $request->user()->tenant;

        $result = $this->cinetPayService->initializePayment(
            $tenant,
            $invoice->balance_due,
            "Paiement facture {$invoice->number}",
            $invoice,
            $invoice->subscription,
            [
                'name' => $invoice->billing_name,
                'email' => $invoice->billing_email,
            ]
        );

        if ($result['success']) {
            return Inertia::location($result['payment_url']);
        }

        return back()->withErrors(['payment' => $result['error']]);
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoice(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $pdfService = new InvoicePdfService();
        return $pdfService->download($invoice);
    }

    /**
     * View invoice PDF inline.
     */
    public function viewInvoicePdf(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $pdfService = new InvoicePdfService();
        return $pdfService->stream($invoice);
    }

    /**
     * Display payment history.
     */
    public function payments(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        $payments = Payment::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn($payment) => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->getFormattedAmount(),
                'status' => $payment->status,
                'status_label' => $payment->getStatusLabel(),
                'status_color' => $payment->getStatusColor(),
                'payment_method' => $payment->getPaymentMethodLabel(),
                'is_refund' => $payment->is_refund,
                'invoice_number' => $payment->invoice?->number,
                'date' => $payment->created_at->format('d/m/Y H:i'),
                'completed_at' => $payment->completed_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Billing/Payments', [
            'payments' => $payments,
        ]);
    }
}
