<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure plans exist
        $this->call(PlanSeeder::class);

        // Get the first tenant (created by TestDataSeeder)
        $tenant = Tenant::first();

        if (!$tenant) {
            $this->command->warn('No tenant found. Please run TestDataSeeder first.');
            return;
        }

        // Get the Pro plan
        $proPlan = Plan::where('slug', 'pro')->first();

        if (!$proPlan) {
            $this->command->warn('Pro plan not found.');
            return;
        }

        // Create an active subscription
        $subscription = Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $proPlan->id,
                'billing_cycle' => 'monthly',
                'price' => $proPlan->price_monthly,
                'currency' => 'XOF',
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now()->subDays(15),
                'ends_at' => now()->addDays(15),
                'emails_used' => 1250,
                'contacts_count' => 506,
                'campaigns_used' => 7,
            ]
        );

        $this->command->info("Subscription created for tenant: {$tenant->name}");

        // Create sample invoices
        $invoices = [];

        // Paid invoice (past)
        $invoices[] = Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_PAID,
            'subtotal' => 24900,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 24900,
            'amount_paid' => 24900,
            'balance_due' => 0,
            'currency' => 'XOF',
            'issue_date' => now()->subMonths(2),
            'due_date' => now()->subMonths(2)->addDays(7),
            'paid_at' => now()->subMonths(2)->addDays(2),
            'billing_name' => $tenant->name,
            'billing_email' => $tenant->email,
            'billing_country' => 'BF',
            'line_items' => [
                [
                    'description' => 'Abonnement Pro - Mensuel',
                    'quantity' => 1,
                    'unit_price' => 24900,
                    'total' => 24900,
                ],
            ],
        ]);

        // Another paid invoice
        $invoices[] = Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_PAID,
            'subtotal' => 24900,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 24900,
            'amount_paid' => 24900,
            'balance_due' => 0,
            'currency' => 'XOF',
            'issue_date' => now()->subMonth(),
            'due_date' => now()->subMonth()->addDays(7),
            'paid_at' => now()->subMonth()->addDays(1),
            'billing_name' => $tenant->name,
            'billing_email' => $tenant->email,
            'billing_country' => 'BF',
            'line_items' => [
                [
                    'description' => 'Abonnement Pro - Mensuel',
                    'quantity' => 1,
                    'unit_price' => 24900,
                    'total' => 24900,
                ],
            ],
        ]);

        // Current pending invoice
        $invoices[] = Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => 24900,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 24900,
            'amount_paid' => 0,
            'balance_due' => 24900,
            'currency' => 'XOF',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'billing_name' => $tenant->name,
            'billing_email' => $tenant->email,
            'billing_country' => 'BF',
            'line_items' => [
                [
                    'description' => 'Abonnement Pro - Mensuel (Renouvellement)',
                    'quantity' => 1,
                    'unit_price' => 24900,
                    'total' => 24900,
                ],
            ],
        ]);

        $this->command->info(count($invoices) . ' invoices created.');

        // Create sample payments
        $payments = [];

        // Payment for first invoice
        $payments[] = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoices[0]->id,
            'subscription_id' => $subscription->id,
            'amount' => 24900,
            'currency' => 'XOF',
            'status' => Payment::STATUS_COMPLETED,
            'payment_method' => Payment::METHOD_ORANGE_MONEY,
            'cinetpay_transaction_id' => 'CP-' . strtoupper(substr(md5(uniqid()), 0, 12)),
            'external_reference' => 'OM-' . strtoupper(substr(md5(uniqid()), 0, 10)),
            'phone_number' => '+22670123456',
            'customer_name' => $tenant->name,
            'initiated_at' => now()->subMonths(2)->addDays(2),
            'completed_at' => now()->subMonths(2)->addDays(2),
        ]);

        // Payment for second invoice
        $payments[] = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoices[1]->id,
            'subscription_id' => $subscription->id,
            'amount' => 24900,
            'currency' => 'XOF',
            'status' => Payment::STATUS_COMPLETED,
            'payment_method' => Payment::METHOD_WAVE,
            'cinetpay_transaction_id' => 'CP-' . strtoupper(substr(md5(uniqid()), 0, 12)),
            'external_reference' => 'WAVE-' . strtoupper(substr(md5(uniqid()), 0, 10)),
            'phone_number' => '+22670123456',
            'customer_name' => $tenant->name,
            'initiated_at' => now()->subMonth()->addDays(1),
            'completed_at' => now()->subMonth()->addDays(1),
        ]);

        // Failed payment attempt
        $payments[] = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoices[2]->id,
            'subscription_id' => $subscription->id,
            'amount' => 24900,
            'currency' => 'XOF',
            'status' => Payment::STATUS_FAILED,
            'payment_method' => Payment::METHOD_MTN_MONEY,
            'phone_number' => '+22670123456',
            'customer_name' => $tenant->name,
            'failure_reason' => 'Solde insuffisant',
            'initiated_at' => now()->subDays(2),
            'failed_at' => now()->subDays(2),
        ]);

        $this->command->info(count($payments) . ' payments created.');
        $this->command->info('Billing test data seeded successfully!');
    }
}
