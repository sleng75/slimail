<?php

namespace App\Services\Amazon;

use App\Contracts\EmailServiceInterface;
use App\Models\Contact;
use App\Models\SentEmail;
use App\Models\Tenant;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class SESService implements EmailServiceInterface
{
    protected ?SesClient $client = null;
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'region' => config('services.ses.region', 'eu-west-3'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ];
    }

    /**
     * Get the SES client.
     */
    protected function getClient(): SesClient
    {
        if (!$this->client) {
            $this->client = new SesClient($this->config);
        }
        return $this->client;
    }

    /**
     * Send a transactional email.
     */
    public function sendEmail(array $params): array
    {
        $result = [
            'success' => false,
            'message_id' => null,
            'error' => null,
        ];

        try {
            $emailParams = [
                'Source' => $this->formatEmail($params['from_email'], $params['from_name'] ?? null),
                'Destination' => [
                    'ToAddresses' => [$params['to_email']],
                ],
                'Message' => [
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => $params['subject'],
                    ],
                    'Body' => [],
                ],
            ];

            // Add HTML body
            if (!empty($params['html_content'])) {
                $emailParams['Message']['Body']['Html'] = [
                    'Charset' => 'UTF-8',
                    'Data' => $params['html_content'],
                ];
            }

            // Add text body
            if (!empty($params['text_content'])) {
                $emailParams['Message']['Body']['Text'] = [
                    'Charset' => 'UTF-8',
                    'Data' => $params['text_content'],
                ];
            }

            // Add reply-to
            if (!empty($params['reply_to'])) {
                $emailParams['ReplyToAddresses'] = [$params['reply_to']];
            }

            // Add configuration set for tracking
            if (!empty($params['configuration_set'])) {
                $emailParams['ConfigurationSetName'] = $params['configuration_set'];
            }

            // Add tags for tracking
            if (!empty($params['tags'])) {
                $emailParams['Tags'] = array_map(function ($key, $value) {
                    return ['Name' => $key, 'Value' => (string) $value];
                }, array_keys($params['tags']), $params['tags']);
            }

            $response = $this->getClient()->sendEmail($emailParams);

            $result['success'] = true;
            $result['message_id'] = $response->get('MessageId');

        } catch (AwsException $e) {
            $result['error'] = $e->getAwsErrorMessage() ?? $e->getMessage();
            Log::error('SES Send Error: ' . $result['error'], [
                'to' => $params['to_email'],
                'subject' => $params['subject'],
            ]);
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('SES Send Exception: ' . $result['error']);
        }

        return $result;
    }

    /**
     * Send a raw email (for attachments).
     */
    public function sendRawEmail(array $params): array
    {
        $result = [
            'success' => false,
            'message_id' => null,
            'error' => null,
        ];

        try {
            $rawMessage = $this->buildRawMessage($params);

            $response = $this->getClient()->sendRawEmail([
                'RawMessage' => [
                    'Data' => $rawMessage,
                ],
                'ConfigurationSetName' => $params['configuration_set'] ?? null,
            ]);

            $result['success'] = true;
            $result['message_id'] = $response->get('MessageId');

        } catch (AwsException $e) {
            $result['error'] = $e->getAwsErrorMessage() ?? $e->getMessage();
            Log::error('SES Raw Send Error: ' . $result['error']);
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('SES Raw Send Exception: ' . $result['error']);
        }

        return $result;
    }

    /**
     * Build a raw MIME message.
     */
    protected function buildRawMessage(array $params): string
    {
        $boundary = uniqid('boundary_');
        $headers = [];

        // From
        $headers[] = 'From: ' . $this->formatEmail($params['from_email'], $params['from_name'] ?? null);

        // To
        $headers[] = 'To: ' . $params['to_email'];

        // Reply-To
        if (!empty($params['reply_to'])) {
            $headers[] = 'Reply-To: ' . $params['reply_to'];
        }

        // Subject
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($params['subject']) . '?=';

        // MIME
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

        $message = implode("\r\n", $headers) . "\r\n\r\n";

        // HTML/Text content
        $contentBoundary = uniqid('content_');
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$contentBoundary}\"\r\n\r\n";

        if (!empty($params['text_content'])) {
            $message .= "--{$contentBoundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($params['text_content'])) . "\r\n";
        }

        if (!empty($params['html_content'])) {
            $message .= "--{$contentBoundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($params['html_content'])) . "\r\n";
        }

        $message .= "--{$contentBoundary}--\r\n";

        // Attachments
        if (!empty($params['attachments'])) {
            foreach ($params['attachments'] as $attachment) {
                $message .= "--{$boundary}\r\n";
                $message .= "Content-Type: {$attachment['content_type']}; name=\"{$attachment['filename']}\"\r\n";
                $message .= "Content-Disposition: attachment; filename=\"{$attachment['filename']}\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $message .= chunk_split($attachment['content']) . "\r\n";
            }
        }

        $message .= "--{$boundary}--";

        return $message;
    }

    /**
     * Verify an email address.
     */
    public function verifyEmailIdentity(string $email): array
    {
        try {
            $this->getClient()->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);

            return ['success' => true];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage()];
        }
    }

    /**
     * Verify a domain.
     */
    public function verifyDomainIdentity(string $domain): array
    {
        try {
            $result = $this->getClient()->verifyDomainIdentity([
                'Domain' => $domain,
            ]);

            return [
                'success' => true,
                'verification_token' => $result->get('VerificationToken'),
            ];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage()];
        }
    }

    /**
     * Get DKIM tokens for a domain.
     */
    public function verifyDomainDkim(string $domain): array
    {
        try {
            $result = $this->getClient()->verifyDomainDkim([
                'Domain' => $domain,
            ]);

            return [
                'success' => true,
                'dkim_tokens' => $result->get('DkimTokens'),
            ];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage()];
        }
    }

    /**
     * Get identity verification status.
     */
    public function getIdentityVerificationAttributes(array $identities): array
    {
        try {
            $result = $this->getClient()->getIdentityVerificationAttributes([
                'Identities' => $identities,
            ]);

            return [
                'success' => true,
                'attributes' => $result->get('VerificationAttributes'),
            ];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage()];
        }
    }

    /**
     * List verified identities.
     */
    public function listIdentities(string $type = 'EmailAddress'): array
    {
        try {
            $result = $this->getClient()->listIdentities([
                'IdentityType' => $type,
            ]);

            return [
                'success' => true,
                'identities' => $result->get('Identities'),
            ];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage()];
        }
    }

    /**
     * Get sending quota.
     */
    public function getSendQuota(): array
    {
        try {
            $result = $this->getClient()->getSendQuota();

            return [
                'success' => true,
                'max_24_hour_send' => $result->get('Max24HourSend'),
                'max_send_rate' => $result->get('MaxSendRate'),
                'sent_last_24_hours' => $result->get('SentLast24Hours'),
            ];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage()];
        }
    }

    /**
     * Get sending statistics.
     */
    public function getSendStatistics(): array
    {
        try {
            $result = $this->getClient()->getSendStatistics();

            return [
                'success' => true,
                'data_points' => $result->get('SendDataPoints'),
            ];
        } catch (AwsException $e) {
            return ['success' => false, 'error' => $e->getAwsErrorMessage()];
        }
    }

    /**
     * Format email with name.
     */
    protected function formatEmail(string $email, ?string $name = null): string
    {
        if ($name) {
            // Encode name if it contains special characters
            $encodedName = '=?UTF-8?B?' . base64_encode($name) . '?=';
            return "{$encodedName} <{$email}>";
        }
        return $email;
    }

    /**
     * Check if SES is configured.
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.ses.key')) && !empty(config('services.ses.secret'));
    }

    /**
     * Check if the service is in mock mode.
     */
    public function isMockMode(): bool
    {
        return false;
    }
}
