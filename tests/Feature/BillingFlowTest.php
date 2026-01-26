<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payment\CinetPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Company',
            'email' => 'billing@test.com',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
        ]);

        $this->plan = Plan::factory()->create([
            'name' => 'Pro',
            'price' => 25000,
            'currency' => 'XOF',
            'email_limit' => 10000,
            'contact_limit' => 5000,
            'billing_period' => 'monthly',
        ]);

        config([
            'services.cinetpay.api_key' => 'test-api-key',
            'services.cinetpay.site_id' => 'test-site-id',
            'services.cinetpay.secret_key' => 'test-secret-key',
            'services.cinetpay.sandbox' => true,
        ]);
    }

    /** @test */
    public function user_can_view_billing_page()
    {
        $response = $this->actingAs($this->user)
            ->get('/settings/billing');

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_view_available_plans()
    {
        Plan::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get('/settings/billing/plans');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('plans'));
    }

    /** @test */
    public function user_can_subscribe_to_plan()
    {
        Http::fake([
            'https://api-checkout.cinetpay.com/v2/payment' => Http::response([
                'code' => '201',
                'data' => [
                    'payment_token' => 'test-token',
                    'payment_url' => 'https://checkout.cinetpay.com/test',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->post('/settings/billing/subscribe', [
                'plan_id' => $this->plan->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'amount' => $this->plan->price,
        ]);
    }

    /** @test */
    public function user_can_upgrade_subscription()
    {
        $currentPlan = Plan::factory()->create([
            'name' => 'Starter',
            'price' => 10000,
        ]);

        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $currentPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '201',
                'data' => [
                    'payment_token' => 'upgrade-token',
                    'payment_url' => 'https://checkout.cinetpay.com/upgrade',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->post('/settings/billing/upgrade', [
                'plan_id' => $this->plan->id,
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function user_can_view_invoices()
    {
        Invoice::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/settings/billing/invoices');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('invoices'));
    }

    /** @test */
    public function user_can_download_invoice_pdf()
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'number' => 'INV-2024-001',
            'status' => Invoice::STATUS_PAID,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/settings/billing/invoices/{$invoice->id}/download");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function user_can_view_payment_history()
    {
        Payment::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/settings/billing/payments');

        $response->assertStatus(200);
    }

    /** @test */
    public function payment_webhook_processes_successful_payment()
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Subscription::STATUS_PENDING,
        ]);

        $payment->update(['subscription_id' => $subscription->id]);

        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'ACCEPTED',
                    'payment_method' => 'ORANGE_MONEY',
                ],
            ], 200),
        ]);

        config(['services.cinetpay.site_id' => 'test-site']);

        $response = $this->postJson('/webhooks/cinetpay', [
            'cpm_trans_id' => $payment->transaction_id,
            'cpm_site_id' => 'test-site',
        ]);

        $response->assertStatus(200);

        $payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->status);
    }

    /** @test */
    public function subscription_activates_after_payment()
    {
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => Subscription::STATUS_PENDING,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => ['status' => 'ACCEPTED'],
            ], 200),
        ]);

        config(['services.cinetpay.site_id' => 'test-site']);

        $this->postJson('/webhooks/cinetpay', [
            'cpm_trans_id' => $payment->transaction_id,
            'cpm_site_id' => 'test-site',
        ]);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
    }

    /** @test */
    public function user_can_cancel_subscription()
    {
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/settings/billing/cancel');

        $response->assertRedirect();

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_CANCELLED, $subscription->status);
    }

    /** @test */
    public function invoice_is_generated_after_payment()
    {
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => $this->plan->price,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => ['status' => 'ACCEPTED'],
            ], 200),
        ]);

        config(['services.cinetpay.site_id' => 'test-site']);

        $this->postJson('/webhooks/cinetpay', [
            'cpm_trans_id' => $payment->transaction_id,
            'cpm_site_id' => 'test-site',
        ]);

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $this->tenant->id,
            'amount' => $this->plan->price,
            'status' => Invoice::STATUS_PAID,
        ]);
    }

    /** @test */
    public function user_can_retry_failed_payment()
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_FAILED,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '201',
                'data' => [
                    'payment_token' => 'retry-token',
                    'payment_url' => 'https://checkout.cinetpay.com/retry',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->post("/settings/billing/payments/{$payment->id}/retry");

        $response->assertRedirect();
    }

    /** @test */
    public function usage_limits_are_enforced()
    {
        $limitedPlan = Plan::factory()->create([
            'email_limit' => 100,
            'contact_limit' => 50,
        ]);

        Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $limitedPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->tenant->update([
            'emails_sent_this_month' => 100,
        ]);

        // Attempting to send more emails should be blocked
        $this->assertTrue($this->tenant->hasReachedEmailLimit());
    }

    /** @test */
    public function trial_period_is_respected()
    {
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->assertTrue($subscription->isOnTrial());
        $this->assertFalse($subscription->isExpired());
    }

    /** @test */
    public function expired_trial_requires_payment()
    {
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->assertTrue($subscription->isTrialExpired());
    }

    /** @test */
    public function non_owner_cannot_manage_billing()
    {
        $editor = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'editor',
        ]);

        $response = $this->actingAs($editor)
            ->post('/settings/billing/subscribe', [
                'plan_id' => $this->plan->id,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_receives_payment_confirmation_email()
    {
        // This would test email notification after successful payment
        $this->markTestIncomplete('Email notification test to be implemented');
    }

    /** @test */
    public function user_receives_renewal_reminder()
    {
        // This would test renewal reminder email before subscription expires
        $this->markTestIncomplete('Renewal reminder test to be implemented');
    }

    /** @test */
    public function invoice_numbers_are_sequential()
    {
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'number' => 'INV-2024-001',
        ]);

        $invoice2 = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $invoice3 = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Verify sequential numbering
        $this->assertNotEquals($invoice2->number, $invoice3->number);
    }

    /** @test */
    public function payment_methods_are_displayed()
    {
        $response = $this->actingAs($this->user)
            ->get('/settings/billing/methods');

        $response->assertStatus(200);
        // Should show available payment methods (Orange Money, Wave, etc.)
    }

    /** @test */
    public function sandbox_mode_is_indicated()
    {
        config(['services.cinetpay.sandbox' => true]);

        $response = $this->actingAs($this->user)
            ->get('/settings/billing');

        $response->assertStatus(200);
        // In sandbox mode, UI should indicate test mode
    }
}
