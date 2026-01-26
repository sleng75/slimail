<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Tenant;
use App\Services\Email\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SendController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send a transactional email.
     *
     * POST /api/v1/send
     */
    public function send(Request $request): JsonResponse
    {
        // Validate API key
        $apiKey = $this->validateApiKey($request);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or inactive API key',
                'code' => 'INVALID_API_KEY',
            ], 401);
        }

        // Check rate limiting
        if (!$this->checkRateLimit($apiKey)) {
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'code' => 'RATE_LIMIT_EXCEEDED',
            ], 429);
        }

        // Check IP whitelist
        if (!$this->checkIpWhitelist($apiKey, $request->ip())) {
            return response()->json([
                'success' => false,
                'error' => 'IP address not allowed',
                'code' => 'IP_NOT_ALLOWED',
            ], 403);
        }

        // Check permissions
        if (!$apiKey->hasPermission('send')) {
            return response()->json([
                'success' => false,
                'error' => 'API key does not have send permission',
                'code' => 'PERMISSION_DENIED',
            ], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'from_email' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'to_email' => 'required|email|max:255',
            'to_name' => 'nullable|string|max:255',
            'reply_to' => 'nullable|email|max:255',
            'subject' => 'required|string|max:998',
            'html_content' => 'required_without:text_content|nullable|string',
            'text_content' => 'required_without:html_content|nullable|string',
            'template_id' => 'nullable|integer|exists:email_templates,id',
            'template_variables' => 'nullable|array',
            'attachments' => 'nullable|array|max:10',
            'attachments.*.filename' => 'required_with:attachments|string|max:255',
            'attachments.*.content' => 'required_with:attachments|string',
            'attachments.*.content_type' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'details' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $tenant = $apiKey->tenant;

        // Check tenant quota
        if (!$this->checkTenantQuota($tenant)) {
            return response()->json([
                'success' => false,
                'error' => 'Email quota exceeded for this period',
                'code' => 'QUOTA_EXCEEDED',
            ], 429);
        }

        // Process template if provided
        if (!empty($data['template_id'])) {
            $template = $tenant->emailTemplates()->find($data['template_id']);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'error' => 'Template not found',
                    'code' => 'TEMPLATE_NOT_FOUND',
                ], 404);
            }

            $variables = $data['template_variables'] ?? [];
            $data['html_content'] = $template->render($variables);

            if (empty($data['subject'])) {
                $data['subject'] = $template->default_subject;
            }

            $template->incrementUsage();
        }

        // Validate attachments size
        if (!empty($data['attachments'])) {
            $totalSize = 0;
            foreach ($data['attachments'] as $attachment) {
                $decoded = base64_decode($attachment['content'], true);
                if ($decoded === false) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid base64 content in attachment',
                        'code' => 'INVALID_ATTACHMENT',
                    ], 422);
                }
                $totalSize += strlen($decoded);
            }

            // Max 10MB total attachments
            if ($totalSize > 10 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'error' => 'Total attachment size exceeds 10MB limit',
                    'code' => 'ATTACHMENT_SIZE_EXCEEDED',
                ], 422);
            }
        }

        try {
            // Send the email
            $result = $this->emailService->sendTransactional($data, $tenant, $apiKey);

            // Record API usage
            $apiKey->recordUsage();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message_id' => $result['message_id'],
                    'email_id' => $result['email_id'],
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'code' => 'SEND_FAILED',
                    'email_id' => $result['email_id'] ?? null,
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('API send error', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id,
                'api_key_id' => $apiKey->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Get email status.
     *
     * GET /api/v1/emails/{id}
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $apiKey = $this->validateApiKey($request);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or inactive API key',
                'code' => 'INVALID_API_KEY',
            ], 401);
        }

        if (!$apiKey->hasPermission('read')) {
            return response()->json([
                'success' => false,
                'error' => 'API key does not have read permission',
                'code' => 'PERMISSION_DENIED',
            ], 403);
        }

        $email = $apiKey->tenant->sentEmails()->find($id);

        if (!$email) {
            return response()->json([
                'success' => false,
                'error' => 'Email not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $email->id,
                'message_id' => $email->message_id,
                'status' => $email->status,
                'from_email' => $email->from_email,
                'to_email' => $email->to_email,
                'subject' => $email->subject,
                'sent_at' => $email->sent_at?->toIso8601String(),
                'delivered_at' => $email->delivered_at?->toIso8601String(),
                'opened_at' => $email->opened_at?->toIso8601String(),
                'clicked_at' => $email->clicked_at?->toIso8601String(),
                'bounced_at' => $email->bounced_at?->toIso8601String(),
                'bounce_type' => $email->bounce_type,
                'metadata' => $email->metadata,
            ],
        ]);
    }

    /**
     * Get email events.
     *
     * GET /api/v1/emails/{id}/events
     */
    public function events(Request $request, int $id): JsonResponse
    {
        $apiKey = $this->validateApiKey($request);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or inactive API key',
                'code' => 'INVALID_API_KEY',
            ], 401);
        }

        if (!$apiKey->hasPermission('read')) {
            return response()->json([
                'success' => false,
                'error' => 'API key does not have read permission',
                'code' => 'PERMISSION_DENIED',
            ], 403);
        }

        $email = $apiKey->tenant->sentEmails()->find($id);

        if (!$email) {
            return response()->json([
                'success' => false,
                'error' => 'Email not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $events = $email->events()
            ->orderBy('event_at', 'desc')
            ->get()
            ->map(fn($event) => [
                'type' => $event->event_type,
                'timestamp' => $event->event_at->toIso8601String(),
                'data' => array_filter([
                    'link_url' => $event->link_url,
                    'user_agent' => $event->user_agent,
                    'ip_address' => $event->ip_address,
                    'bounce_type' => $event->bounce_type,
                    'bounce_subtype' => $event->bounce_subtype,
                ]),
            ]);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Validate API key from request.
     */
    protected function validateApiKey(Request $request): ?ApiKey
    {
        $token = $request->bearerToken();

        if (!$token) {
            // Try X-API-Key header
            $token = $request->header('X-API-Key');
        }

        if (!$token) {
            return null;
        }

        $apiKey = ApiKey::where('key', $token)
            ->where('is_active', true)
            ->first();

        if (!$apiKey) {
            return null;
        }

        // Check expiration
        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return null;
        }

        return $apiKey;
    }

    /**
     * Check rate limiting for API key.
     */
    protected function checkRateLimit(ApiKey $apiKey): bool
    {
        if (!$apiKey->rate_limit) {
            return true;
        }

        $key = "api_rate_limit:{$apiKey->id}";
        $requests = cache()->get($key, 0);

        if ($requests >= $apiKey->rate_limit) {
            return false;
        }

        cache()->put($key, $requests + 1, 60); // 1 minute window

        return true;
    }

    /**
     * Check IP whitelist.
     */
    protected function checkIpWhitelist(ApiKey $apiKey, string $ip): bool
    {
        if (empty($apiKey->ip_whitelist)) {
            return true;
        }

        return in_array($ip, $apiKey->ip_whitelist);
    }

    /**
     * Check tenant email quota.
     */
    protected function checkTenantQuota(Tenant $tenant): bool
    {
        // For now, always return true
        // In the future, check subscription limits
        return true;
    }
}
