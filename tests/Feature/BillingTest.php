<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Plan $freePlan;
    protected Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'email' => 'test@example.com',
            'owner_name' => 'Test Owner',
        ]);

        // Create user
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        // Create plans
        $this->freePlan = Plan::create([
            'name' => 'Gratuit',
            'slug' => 'free',
            'description' => 'Plan gratuit',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'currency' => 'XOF',
            'limits' => ['emails' => 500, 'contacts' => 100, 'users' => 1],
            'features' => ['email_editor' => true],
            'is_active' => true,
            'is_free' => true,
            'sort_order' => 1,
        ]);

        $this->proPlan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Plan professionnel',
            'price_monthly' => 29900,
            'price_yearly' => 299000,
            'currency' => 'XOF',
            'limits' => ['emails' => 25000, 'contacts' => 5000, 'users' => 5],
            'features' => ['email_editor' => true, 'automation' => true],
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 2,
        ]);
    }

    public function test_billing_page_displays_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Billing/Index'));
    }

    public function test_plans_page_displays_available_plans(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.plans'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Billing/Plans')
            ->has('plans', 2)
        );
    }

    public function test_user_can_subscribe_to_plan_with_trial(): void
    {
        $response = $this->actingAs($this->user)->post(route('billing.subscribe', $this->proPlan), [
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('billing.index'));

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->proPlan->id,
            'status' => Subscription::STATUS_TRIALING,
        ]);
    }

    public function test_subscription_creates_invoice(): void
    {
        $this->actingAs($this->user)->post(route('billing.subscribe', $this->proPlan), [
            'billing_cycle' => 'monthly',
        ]);

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $this->tenant->id,
            'total' => $this->proPlan->price_monthly,
            'status' => Invoice::STATUS_PENDING,
        ]);
    }

    public function test_invoices_page_displays(): void
    {
        // Create a subscription and invoice
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->proPlan->id,
            'billing_cycle' => 'monthly',
            'price' => 29900,
            'currency' => 'XOF',
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        Invoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'number' => 'FA-2026-000001',
            'sequence_number' => 1,
            'subtotal' => 29900,
            'tax' => 0,
            'total' => 29900,
            'currency' => 'XOF',
            'status' => Invoice::STATUS_PENDING,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'billing_name' => 'Test Company',
            'billing_email' => 'test@example.com',
            'line_items' => [['description' => 'Pro - Mensuel', 'amount' => 29900]],
        ]);

        $response = $this->actingAs($this->user)->get(route('billing.invoices'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Billing/Invoices')
            ->has('invoices.data', 1)
        );
    }

    public function test_payments_page_displays(): void
    {
        $response = $this->actingAs($this->user)->get(route('billing.payments'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Billing/Payments'));
    }

    public function test_plan_yearly_discount_calculation(): void
    {
        $monthlyPrice = $this->proPlan->price_monthly;
        $yearlyPrice = $this->proPlan->price_yearly;
        $expectedMonthlyTotal = $monthlyPrice * 12;
        $savings = $expectedMonthlyTotal - $yearlyPrice;

        $this->assertGreaterThan(0, $savings);
        $this->assertEquals(59800, $savings); // 12 * 29900 - 299000 = 59800
    }

    public function test_subscription_can_be_canceled(): void
    {
        // Create active subscription
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->proPlan->id,
            'billing_cycle' => 'monthly',
            'price' => 29900,
            'currency' => 'XOF',
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user)->post(route('billing.cancel'), [
            'reason' => 'Testing cancellation',
        ]);

        $response->assertRedirect(route('billing.index'));

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'status' => Subscription::STATUS_CANCELED,
        ]);
    }

    public function test_free_plan_does_not_require_payment(): void
    {
        $response = $this->actingAs($this->user)->post(route('billing.subscribe', $this->freePlan), [
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('billing.index'));

        $subscription = Subscription::where('tenant_id', $this->tenant->id)->first();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
    }

    public function test_invoice_number_format(): void
    {
        // Get next sequence number
        $lastSequence = Invoice::whereYear('created_at', now()->year)->max('sequence_number') ?? 0;
        $nextSequence = $lastSequence + 1;

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'number' => Invoice::generateNumber(),
            'sequence_number' => $nextSequence,
            'subtotal' => 29900,
            'tax' => 0,
            'total' => 29900,
            'currency' => 'XOF',
            'status' => Invoice::STATUS_PENDING,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'billing_name' => 'Test',
            'billing_email' => 'test@example.com',
            'line_items' => [],
        ]);

        $this->assertMatchesRegularExpression('/^FA-\d{4}-\d{6}$/', $invoice->number);
    }
}
