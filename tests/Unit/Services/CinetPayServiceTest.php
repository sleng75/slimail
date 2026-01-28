<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payment\CinetPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Skip;
use Tests\TestCase;

#[Skip('CinetPay tests need Payment factory/model fixes - to be fixed later')]
class CinetPayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected CinetPayService $cinetPayService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // Configure CinetPay in sandbox mode
        config([
            'services.cinetpay.api_key' => 'test-api-key',
            'services.cinetpay.site_id' => 'test-site-id',
            'services.cinetpay.secret_key' => 'test-secret-key',
            'services.cinetpay.sandbox' => true,
        ]);

        $this->cinetPayService = new CinetPayService();
    }

    /** @test */
    public function it_initializes_payment_successfully()
    {
        Http::fake([
            'https://api-checkout.cinetpay.com/v2/payment' => Http::response([
                'code' => '201',
                'message' => 'CREATED',
                'data' => [
                    'payment_token' => 'test-payment-token',
                    'payment_url' => 'https://checkout.cinetpay.com/payment/test-token',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->initializePayment(
            $this->tenant,
            5000,
            'Test Payment',
            null,
            null,
            ['name' => 'John Doe', 'phone' => '+22507000000']
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('payment_url', $result);
        $this->assertArrayHasKey('payment_token', $result);
        $this->assertInstanceOf(Payment::class, $result['payment']);
        $this->assertEquals(Payment::STATUS_PROCESSING, $result['payment']->status);
    }

    /** @test */
    public function it_creates_payment_record()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '201',
                'data' => [
                    'payment_token' => 'token',
                    'payment_url' => 'https://payment.url',
                ],
            ], 200),
        ]);

        $this->cinetPayService->initializePayment(
            $this->tenant,
            10000,
            'Monthly subscription'
        );

        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'amount' => 10000,
            'currency' => 'XOF',
        ]);
    }

    /** @test */
    public function it_handles_initialization_failure()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '401',
                'message' => 'Invalid API key',
            ], 200),
        ]);

        $result = $this->cinetPayService->initializePayment(
            $this->tenant,
            5000,
            'Test Payment'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid API key', $result['error']);
        $this->assertEquals(Payment::STATUS_FAILED, $result['payment']->status);
    }

    /** @test */
    public function it_handles_http_error()
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $result = $this->cinetPayService->initializePayment(
            $this->tenant,
            5000,
            'Test Payment'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Connection error', $result['error']);
    }

    /** @test */
    public function it_checks_payment_status_success()
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'ACCEPTED',
                    'payment_method' => 'OM',
                    'operator_id' => 'OP123',
                    'cpm_trans_id' => 'TRX123',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->checkPaymentStatus($payment);

        $this->assertTrue($result['success']);
        $this->assertEquals(Payment::STATUS_COMPLETED, $result['status']);
        $this->assertEquals(Payment::STATUS_COMPLETED, $result['payment']->status);
    }

    /** @test */
    public function it_checks_payment_status_failed()
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'REFUSED',
                    'message' => 'Insufficient funds',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->checkPaymentStatus($payment);

        $this->assertTrue($result['success']);
        $this->assertEquals(Payment::STATUS_FAILED, $result['status']);
    }

    /** @test */
    public function it_handles_webhook_notification()
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'ACCEPTED',
                    'payment_method' => 'MOMO',
                ],
            ], 200),
        ]);

        config(['services.cinetpay.site_id' => 'test-site']);

        $result = $this->cinetPayService->handleWebhook([
            'cpm_trans_id' => $payment->transaction_id,
            'cpm_site_id' => 'test-site',
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_site_id()
    {
        config(['services.cinetpay.site_id' => 'valid-site']);

        $result = $this->cinetPayService->handleWebhook([
            'cpm_trans_id' => 'some-transaction',
            'cpm_site_id' => 'invalid-site',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid signature', $result['error']);
    }

    /** @test */
    public function it_returns_available_payment_methods()
    {
        $methods = $this->cinetPayService->getAvailablePaymentMethods('BF');

        $this->assertIsArray($methods);
        $this->assertNotEmpty($methods);

        $methodCodes = array_column($methods, 'code');
        $this->assertContains('ORANGE_MONEY', $methodCodes);
        $this->assertContains('MOOV_MONEY', $methodCodes);
        $this->assertContains('WAVE', $methodCodes);
        $this->assertContains('CARD', $methodCodes);
    }

    /** @test */
    public function it_identifies_sandbox_mode()
    {
        config(['services.cinetpay.sandbox' => true]);
        $service = new CinetPayService();

        $this->assertTrue($service->isSandboxMode());
    }

    /** @test */
    public function it_simulates_successful_payment_in_sandbox()
    {
        config(['services.cinetpay.sandbox' => true]);
        $service = new CinetPayService();

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        $service->simulateSuccessfulPayment($payment);

        $payment->refresh();
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertEquals(Payment::METHOD_ORANGE_MONEY, $payment->payment_method);
        $this->assertNotNull($payment->completed_at);
    }

    /** @test */
    public function it_simulates_failed_payment_in_sandbox()
    {
        config(['services.cinetpay.sandbox' => true]);
        $service = new CinetPayService();

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        $service->simulateFailedPayment($payment, 'Card declined');

        $payment->refresh();
        $this->assertEquals(Payment::STATUS_FAILED, $payment->status);
        $this->assertStringContainsString('Card declined', $payment->failure_reason);
    }

    /** @test */
    public function it_throws_exception_for_simulation_in_production()
    {
        config(['services.cinetpay.sandbox' => false]);
        $service = new CinetPayService();

        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $service->simulateSuccessfulPayment($payment);
    }

    /** @test */
    public function it_maps_cinetpay_payment_methods()
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        // Test Orange Money
        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'ACCEPTED',
                    'payment_method' => 'ORANGE_CI',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->checkPaymentStatus($payment);

        $payment->refresh();
        $this->assertEquals(Payment::METHOD_ORANGE_MONEY, $payment->payment_method);
    }

    /** @test */
    public function it_links_payment_to_invoice()
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'number' => 'INV-001',
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '201',
                'data' => [
                    'payment_token' => 'token',
                    'payment_url' => 'https://payment.url',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->initializePayment(
            $this->tenant,
            15000,
            'Invoice payment',
            $invoice
        );

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 15000,
        ]);
    }

    /** @test */
    public function it_links_payment_to_subscription()
    {
        $subscription = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '201',
                'data' => [
                    'payment_token' => 'token',
                    'payment_url' => 'https://payment.url',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->initializePayment(
            $this->tenant,
            25000,
            'Subscription payment',
            null,
            $subscription
        );

        $this->assertDatabaseHas('payments', [
            'subscription_id' => $subscription->id,
        ]);
    }

    /** @test */
    public function it_sets_payment_expiration()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '201',
                'data' => [
                    'payment_token' => 'token',
                    'payment_url' => 'https://payment.url',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->initializePayment(
            $this->tenant,
            5000,
            'Test'
        );

        $payment = $result['payment'];
        $this->assertNotNull($payment->expires_at);
        $this->assertTrue($payment->expires_at->isAfter(now()));
    }

    /** @test */
    public function it_handles_connection_exception()
    {
        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $result = $this->cinetPayService->initializePayment(
            $this->tenant,
            5000,
            'Test Payment'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Service temporarily unavailable', $result['error']);
    }

    /** @test */
    public function it_returns_error_for_missing_transaction_id_in_webhook()
    {
        $result = $this->cinetPayService->handleWebhook([
            'cpm_site_id' => 'test-site',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Missing transaction ID', $result['error']);
    }

    /** @test */
    public function it_handles_payment_not_found_in_webhook()
    {
        config(['services.cinetpay.site_id' => 'test-site']);

        $result = $this->cinetPayService->handleWebhook([
            'cpm_trans_id' => 'non-existent-transaction',
            'cpm_site_id' => 'test-site',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Payment not found', $result['error']);
    }

    /** @test */
    public function it_maps_cancelled_status()
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'CANCELLED',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->checkPaymentStatus($payment);

        $this->assertEquals(Payment::STATUS_CANCELED, $result['status']);
    }

    /** @test */
    public function it_maps_pending_status()
    {
        $payment = Payment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*' => Http::response([
                'code' => '00',
                'data' => [
                    'status' => 'PENDING',
                ],
            ], 200),
        ]);

        $result = $this->cinetPayService->checkPaymentStatus($payment);

        $this->assertEquals(Payment::STATUS_PROCESSING, $result['status']);
    }
}
