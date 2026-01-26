<?php

namespace App\Services\Amazon;

use App\Contracts\EmailServiceInterface;
use App\Models\SentEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mock SES Service for development and testing.
 * Simulates Amazon SES behavior without actually sending emails.
 */
class MockSESService implements EmailServiceInterface
{
    /**
     * Simulated identities (emails and domains).
     */
    protected array $verifiedIdentities = [];

    /**
     * Simulated send statistics.
     */
    protected array $sendStats = [
        'sent' => 0,
        'delivered' => 0,
        'bounced' => 0,
        'complaints' => 0,
    ];

    /**
     * Send a transactional email (simulated).
     */
    public function sendEmail(array $params): array
    {
        // Simulate network delay
        usleep(rand(50000, 200000)); // 50-200ms

        // Generate a fake message ID
        $messageId = $this->generateMessageId();

        // Log the simulated send
        Log::channel('daily')->info('[MOCK SES] Email sent', [
            'message_id' => $messageId,
            'from' => $params['from_email'] ?? 'unknown',
            'to' => $params['to_email'] ?? 'unknown',
            'subject' => $params['subject'] ?? 'No subject',
        ]);

        // Simulate random failures (5% chance)
        if (config('services.ses.mock_failure_rate', 0) > 0) {
            if (rand(1, 100) <= config('services.ses.mock_failure_rate')) {
                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => 'Simulated send failure for testing purposes',
                ];
            }
        }

        // Store email in database for viewing (optional)
        $this->storeSimulatedEmail($params, $messageId);

        return [
            'success' => true,
            'message_id' => $messageId,
            'error' => null,
        ];
    }

    /**
     * Send a raw email (simulated).
     */
    public function sendRawEmail(array $params): array
    {
        // Same as sendEmail for mock purposes
        return $this->sendEmail($params);
    }

    /**
     * Verify an email address (simulated).
     */
    public function verifyEmailIdentity(string $email): array
    {
        Log::channel('daily')->info('[MOCK SES] Email verification requested', [
            'email' => $email,
        ]);

        // Store in session/cache for simulation
        $this->addVerifiedIdentity($email, 'email');

        return [
            'success' => true,
            'message' => "Verification email simulated for: {$email}",
        ];
    }

    /**
     * Verify a domain (simulated).
     */
    public function verifyDomainIdentity(string $domain): array
    {
        Log::channel('daily')->info('[MOCK SES] Domain verification requested', [
            'domain' => $domain,
        ]);

        // Generate a fake verification token
        $token = 'mock_' . Str::random(32);

        // Store in session/cache for simulation
        $this->addVerifiedIdentity($domain, 'domain');

        return [
            'success' => true,
            'verification_token' => $token,
            'dns_record' => [
                'name' => "_amazonses.{$domain}",
                'type' => 'TXT',
                'value' => $token,
            ],
        ];
    }

    /**
     * Get DKIM tokens for a domain (simulated).
     */
    public function verifyDomainDkim(string $domain): array
    {
        Log::channel('daily')->info('[MOCK SES] DKIM verification requested', [
            'domain' => $domain,
        ]);

        // Generate fake DKIM tokens
        $tokens = [
            'mock' . Str::random(24),
            'mock' . Str::random(24),
            'mock' . Str::random(24),
        ];

        return [
            'success' => true,
            'dkim_tokens' => $tokens,
            'dns_records' => array_map(function ($token) use ($domain) {
                return [
                    'name' => "{$token}._domainkey.{$domain}",
                    'type' => 'CNAME',
                    'value' => "{$token}.dkim.amazonses.com",
                ];
            }, $tokens),
        ];
    }

    /**
     * Get identity verification status (simulated).
     */
    public function getIdentityVerificationAttributes(array $identities): array
    {
        $attributes = [];

        foreach ($identities as $identity) {
            // In mock mode, all identities are "verified"
            $attributes[$identity] = [
                'VerificationStatus' => 'Success',
            ];
        }

        return [
            'success' => true,
            'attributes' => $attributes,
        ];
    }

    /**
     * List verified identities (simulated).
     */
    public function listIdentities(string $type = 'EmailAddress'): array
    {
        // Return some mock verified identities
        $identities = [];

        if ($type === 'EmailAddress') {
            $identities = [
                'noreply@slimail.local',
                'support@slimail.local',
            ];
        } else {
            $identities = [
                'slimail.local',
            ];
        }

        return [
            'success' => true,
            'identities' => $identities,
        ];
    }

    /**
     * Get sending quota (simulated).
     */
    public function getSendQuota(): array
    {
        return [
            'success' => true,
            'max_24_hour_send' => 50000,      // Simulated daily limit
            'max_send_rate' => 14,             // Simulated per second
            'sent_last_24_hours' => rand(0, 1000), // Simulated
        ];
    }

    /**
     * Get sending statistics (simulated).
     */
    public function getSendStatistics(): array
    {
        // Generate some fake statistics for the last 14 days
        $dataPoints = [];
        $now = now();

        for ($i = 14; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $sent = rand(100, 500);
            $dataPoints[] = [
                'Timestamp' => $date->toIso8601String(),
                'DeliveryAttempts' => $sent,
                'Bounces' => (int) ($sent * 0.02),
                'Complaints' => (int) ($sent * 0.001),
                'Rejects' => 0,
            ];
        }

        return [
            'success' => true,
            'data_points' => $dataPoints,
        ];
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        // Mock service is always "configured"
        return true;
    }

    /**
     * Check if the service is in mock mode.
     */
    public function isMockMode(): bool
    {
        return true;
    }

    /**
     * Generate a fake message ID that looks like a real SES message ID.
     */
    protected function generateMessageId(): string
    {
        return sprintf(
            'mock-%s-%s-%s',
            Str::random(16),
            now()->format('YmdHis'),
            Str::random(8)
        );
    }

    /**
     * Add an identity to the verified list.
     */
    protected function addVerifiedIdentity(string $identity, string $type): void
    {
        $this->verifiedIdentities[$identity] = [
            'type' => $type,
            'verified_at' => now(),
        ];
    }

    /**
     * Store a simulated email in the database for viewing.
     */
    protected function storeSimulatedEmail(array $params, string $messageId): void
    {
        // Log full email content for debugging
        if (config('services.ses.mock_log_content', true)) {
            Log::channel('daily')->debug('[MOCK SES] Email content', [
                'message_id' => $messageId,
                'html_preview' => Str::limit(strip_tags($params['html_content'] ?? ''), 200),
                'text_content' => Str::limit($params['text_content'] ?? '', 200),
            ]);
        }
    }

    /**
     * Simulate webhook events (bounces, complaints, etc.) for testing.
     * Call this method to trigger fake SNS notifications.
     */
    public function simulateWebhookEvent(SentEmail $sentEmail, string $eventType): array
    {
        $events = [
            'delivery' => [
                'eventType' => 'Delivery',
                'mail' => [
                    'messageId' => $sentEmail->message_id,
                    'timestamp' => now()->toIso8601String(),
                ],
                'delivery' => [
                    'timestamp' => now()->toIso8601String(),
                    'processingTimeMillis' => rand(100, 500),
                    'recipients' => [$sentEmail->contact->email ?? 'test@example.com'],
                ],
            ],
            'bounce' => [
                'eventType' => 'Bounce',
                'mail' => [
                    'messageId' => $sentEmail->message_id,
                    'timestamp' => now()->toIso8601String(),
                ],
                'bounce' => [
                    'bounceType' => 'Permanent',
                    'bounceSubType' => 'General',
                    'timestamp' => now()->toIso8601String(),
                    'bouncedRecipients' => [
                        ['emailAddress' => $sentEmail->contact->email ?? 'test@example.com'],
                    ],
                ],
            ],
            'complaint' => [
                'eventType' => 'Complaint',
                'mail' => [
                    'messageId' => $sentEmail->message_id,
                    'timestamp' => now()->toIso8601String(),
                ],
                'complaint' => [
                    'timestamp' => now()->toIso8601String(),
                    'complainedRecipients' => [
                        ['emailAddress' => $sentEmail->contact->email ?? 'test@example.com'],
                    ],
                ],
            ],
            'open' => [
                'eventType' => 'Open',
                'mail' => [
                    'messageId' => $sentEmail->message_id,
                    'timestamp' => now()->toIso8601String(),
                ],
                'open' => [
                    'timestamp' => now()->toIso8601String(),
                    'userAgent' => 'Mozilla/5.0 (Mock Browser)',
                    'ipAddress' => '127.0.0.1',
                ],
            ],
            'click' => [
                'eventType' => 'Click',
                'mail' => [
                    'messageId' => $sentEmail->message_id,
                    'timestamp' => now()->toIso8601String(),
                ],
                'click' => [
                    'timestamp' => now()->toIso8601String(),
                    'link' => 'https://example.com/mock-link',
                    'userAgent' => 'Mozilla/5.0 (Mock Browser)',
                    'ipAddress' => '127.0.0.1',
                ],
            ],
        ];

        return $events[$eventType] ?? [];
    }
}
