<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CinetPayService
{
    protected string $apiKey;
    protected string $siteId;
    protected string $secretKey;
    protected string $baseUrl;
    protected bool $sandboxMode;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key', '');
        $this->siteId = config('services.cinetpay.site_id', '');
        $this->secretKey = config('services.cinetpay.secret_key', '');
        $this->sandboxMode = config('services.cinetpay.sandbox', true);

        $this->baseUrl = $this->sandboxMode
            ? 'https://api-checkout.cinetpay.com/v2'
            : 'https://api-checkout.cinetpay.com/v2';
    }

    /**
     * Initialize a payment.
     */
    public function initializePayment(
        Tenant $tenant,
        float $amount,
        string $description,
        ?Invoice $invoice = null,
        ?Subscription $subscription = null,
        array $customerData = []
    ): array {
        // Create payment record
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice?->id,
            'subscription_id' => $subscription?->id,
            'amount' => $amount,
            'currency' => 'XOF',
            'status' => Payment::STATUS_PENDING,
            'customer_name' => $customerData['name'] ?? null,
            'phone_number' => $customerData['phone'] ?? null,
            'initiated_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        // Prepare CinetPay payload
        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $payment->transaction_id,
            'amount' => (int) $amount,
            'currency' => 'XOF',
            'alternative_currency' => '',
            'description' => $description,
            'customer_id' => (string) $tenant->id,
            'customer_name' => $customerData['name'] ?? $tenant->name,
            'customer_surname' => $customerData['surname'] ?? '',
            'customer_email' => $customerData['email'] ?? $tenant->email,
            'customer_phone_number' => $customerData['phone'] ?? '',
            'customer_address' => $customerData['address'] ?? '',
            'customer_city' => $customerData['city'] ?? '',
            'customer_country' => $customerData['country'] ?? 'BF',
            'customer_state' => '',
            'customer_zip_code' => '',
            'notify_url' => route('webhooks.cinetpay'),
            'return_url' => route('billing.payment.return', ['payment' => $payment->id]),
            'channels' => 'ALL',
            'metadata' => json_encode([
                'payment_id' => $payment->id,
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice?->id,
                'subscription_id' => $subscription?->id,
            ]),
            'lang' => 'FR',
            'invoice_data' => $invoice ? [
                'invoice_number' => $invoice->number,
                'items' => collect($invoice->line_items)->map(fn($item) => [
                    'name' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_price'],
                ])->toArray(),
            ] : null,
        ];

        try {
            $response = Http::timeout(30)
                ->post($this->baseUrl . '/payment', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['code'] === '201') {
                    // Update payment with CinetPay info
                    $payment->update([
                        'cinetpay_payment_token' => $data['data']['payment_token'] ?? null,
                        'cinetpay_payment_url' => $data['data']['payment_url'] ?? null,
                        'status' => Payment::STATUS_PROCESSING,
                    ]);

                    return [
                        'success' => true,
                        'payment' => $payment,
                        'payment_url' => $data['data']['payment_url'],
                        'payment_token' => $data['data']['payment_token'],
                    ];
                }

                Log::error('CinetPay initialization failed', [
                    'response' => $data,
                    'payment_id' => $payment->id,
                ]);

                $payment->markAsFailed($data['message'] ?? 'Initialization failed');

                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Payment initialization failed',
                    'payment' => $payment,
                ];
            }

            Log::error('CinetPay HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $payment->markAsFailed('HTTP Error: ' . $response->status());

            return [
                'success' => false,
                'error' => 'Connection error',
                'payment' => $payment,
            ];
        } catch (\Exception $e) {
            Log::error('CinetPay exception', [
                'message' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);

            $payment->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'error' => 'Service temporarily unavailable',
                'payment' => $payment,
            ];
        }
    }

    /**
     * Check payment status.
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $payment->transaction_id,
        ];

        try {
            $response = Http::timeout(30)
                ->post($this->baseUrl . '/payment/check', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['code'] === '00') {
                    $paymentData = $data['data'];
                    $status = $this->mapCinetPayStatus($paymentData['status']);

                    // Update payment
                    if ($status === Payment::STATUS_COMPLETED) {
                        $payment->markAsCompleted(
                            $paymentData['payment_method'] ?? null,
                            $paymentData['operator_id'] ?? null
                        );
                        $payment->update([
                            'payment_method' => $this->mapPaymentMethod($paymentData['payment_method'] ?? null),
                            'cinetpay_transaction_id' => $paymentData['cpm_trans_id'] ?? null,
                        ]);
                    } elseif ($status === Payment::STATUS_FAILED) {
                        $payment->markAsFailed($paymentData['message'] ?? 'Payment failed');
                    }

                    return [
                        'success' => true,
                        'status' => $status,
                        'payment' => $payment->fresh(),
                        'raw_data' => $paymentData,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Status check failed',
                ];
            }

            return [
                'success' => false,
                'error' => 'Connection error',
            ];
        } catch (\Exception $e) {
            Log::error('CinetPay status check exception', [
                'message' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);

            return [
                'success' => false,
                'error' => 'Service temporarily unavailable',
            ];
        }
    }

    /**
     * Handle webhook notification.
     */
    public function handleWebhook(array $data): array
    {
        // Verify signature
        if (!$this->verifyWebhookSignature($data)) {
            Log::warning('CinetPay webhook signature verification failed', $data);
            return ['success' => false, 'error' => 'Invalid signature'];
        }

        $transactionId = $data['cpm_trans_id'] ?? $data['transaction_id'] ?? null;

        if (!$transactionId) {
            return ['success' => false, 'error' => 'Missing transaction ID'];
        }

        // Find payment
        $payment = Payment::where('transaction_id', $transactionId)
            ->orWhere('cinetpay_transaction_id', $transactionId)
            ->first();

        if (!$payment) {
            Log::warning('CinetPay webhook: Payment not found', ['transaction_id' => $transactionId]);
            return ['success' => false, 'error' => 'Payment not found'];
        }

        // Check status with CinetPay to confirm
        $statusResult = $this->checkPaymentStatus($payment);

        if ($statusResult['success']) {
            Log::info('CinetPay webhook processed', [
                'payment_id' => $payment->id,
                'status' => $statusResult['status'],
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'status' => $statusResult['status'],
            ];
        }

        return $statusResult;
    }

    /**
     * Map CinetPay status to our status.
     */
    protected function mapCinetPayStatus(string $status): string
    {
        return match(strtoupper($status)) {
            'ACCEPTED', 'SUCCESSFUL' => Payment::STATUS_COMPLETED,
            'PENDING' => Payment::STATUS_PROCESSING,
            'REFUSED', 'FAILED' => Payment::STATUS_FAILED,
            'CANCELLED' => Payment::STATUS_CANCELED,
            default => Payment::STATUS_PENDING,
        };
    }

    /**
     * Map CinetPay payment method to our method.
     */
    protected function mapPaymentMethod(?string $method): ?string
    {
        if (!$method) {
            return null;
        }

        $method = strtoupper($method);

        return match(true) {
            str_contains($method, 'ORANGE') => Payment::METHOD_ORANGE_MONEY,
            str_contains($method, 'MOOV') => Payment::METHOD_MOOV_MONEY,
            str_contains($method, 'MTN') => Payment::METHOD_MTN_MONEY,
            str_contains($method, 'WAVE') => Payment::METHOD_WAVE,
            str_contains($method, 'VISA'), str_contains($method, 'MASTERCARD'), str_contains($method, 'CARD') => Payment::METHOD_CARD,
            default => Payment::METHOD_OTHER,
        };
    }

    /**
     * Verify webhook signature.
     */
    protected function verifyWebhookSignature(array $data): bool
    {
        // CinetPay sends the site_id, we verify it matches
        $siteId = $data['cpm_site_id'] ?? $data['site_id'] ?? null;

        if ($siteId && $siteId !== $this->siteId) {
            return false;
        }

        // Additional signature verification if available
        // This depends on CinetPay's specific implementation
        return true;
    }

    /**
     * Get available payment methods.
     */
    public function getAvailablePaymentMethods(string $country = 'BF'): array
    {
        return [
            [
                'code' => 'ORANGE_MONEY',
                'name' => 'Orange Money',
                'icon' => 'orange-money.png',
                'countries' => ['BF', 'CI', 'SN', 'ML'],
            ],
            [
                'code' => 'MOOV_MONEY',
                'name' => 'Moov Money',
                'icon' => 'moov-money.png',
                'countries' => ['BF', 'CI', 'BJ', 'TG'],
            ],
            [
                'code' => 'MTN_MONEY',
                'name' => 'MTN Money',
                'icon' => 'mtn-money.png',
                'countries' => ['BF', 'CI', 'BJ', 'CM'],
            ],
            [
                'code' => 'WAVE',
                'name' => 'Wave',
                'icon' => 'wave.png',
                'countries' => ['SN', 'CI', 'BF', 'ML'],
            ],
            [
                'code' => 'CARD',
                'name' => 'Carte bancaire',
                'icon' => 'card.png',
                'countries' => ['ALL'],
            ],
        ];
    }

    /**
     * Check if service is in sandbox mode.
     */
    public function isSandboxMode(): bool
    {
        return $this->sandboxMode;
    }

    /**
     * Create a simulated successful payment (for testing).
     */
    public function simulateSuccessfulPayment(Payment $payment): void
    {
        if (!$this->sandboxMode) {
            throw new \RuntimeException('Simulation only available in sandbox mode');
        }

        $payment->markAsCompleted(
            'SIM-' . Str::random(10),
            'SIM-OP-' . Str::random(8)
        );

        $payment->update([
            'payment_method' => Payment::METHOD_ORANGE_MONEY,
            'cinetpay_transaction_id' => 'SIM-' . Str::random(16),
        ]);

        Log::info('Simulated successful payment', ['payment_id' => $payment->id]);
    }

    /**
     * Create a simulated failed payment (for testing).
     */
    public function simulateFailedPayment(Payment $payment, string $reason = 'Insufficient funds'): void
    {
        if (!$this->sandboxMode) {
            throw new \RuntimeException('Simulation only available in sandbox mode');
        }

        $payment->markAsFailed($reason);

        Log::info('Simulated failed payment', [
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]);
    }
}
