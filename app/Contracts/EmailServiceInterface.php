<?php

namespace App\Contracts;

/**
 * Interface for email sending services.
 * Allows for easy swapping between real SES and mock implementations.
 */
interface EmailServiceInterface
{
    /**
     * Send a transactional email.
     *
     * @param array $params Email parameters (from_email, from_name, to_email, subject, html_content, text_content, etc.)
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function sendEmail(array $params): array;

    /**
     * Send a raw email (for attachments).
     *
     * @param array $params Raw email parameters
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function sendRawEmail(array $params): array;

    /**
     * Verify an email address.
     *
     * @param string $email
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function verifyEmailIdentity(string $email): array;

    /**
     * Verify a domain.
     *
     * @param string $domain
     * @return array ['success' => bool, 'verification_token' => string|null, 'error' => string|null]
     */
    public function verifyDomainIdentity(string $domain): array;

    /**
     * Get DKIM tokens for a domain.
     *
     * @param string $domain
     * @return array ['success' => bool, 'dkim_tokens' => array|null, 'error' => string|null]
     */
    public function verifyDomainDkim(string $domain): array;

    /**
     * Get identity verification status.
     *
     * @param array $identities
     * @return array ['success' => bool, 'attributes' => array|null, 'error' => string|null]
     */
    public function getIdentityVerificationAttributes(array $identities): array;

    /**
     * List verified identities.
     *
     * @param string $type 'EmailAddress' or 'Domain'
     * @return array ['success' => bool, 'identities' => array|null, 'error' => string|null]
     */
    public function listIdentities(string $type = 'EmailAddress'): array;

    /**
     * Get sending quota.
     *
     * @return array ['success' => bool, 'max_24_hour_send' => int, 'max_send_rate' => int, 'sent_last_24_hours' => int, 'error' => string|null]
     */
    public function getSendQuota(): array;

    /**
     * Get sending statistics.
     *
     * @return array ['success' => bool, 'data_points' => array|null, 'error' => string|null]
     */
    public function getSendStatistics(): array;

    /**
     * Check if the service is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Check if the service is in mock/simulation mode.
     *
     * @return bool
     */
    public function isMockMode(): bool;
}
