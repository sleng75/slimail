<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\SentEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle SNS webhook for email events.
     *
     * POST /api/webhooks/sns
     */
    public function handleSns(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Log incoming webhook for debugging
        Log::info('SNS Webhook received', ['type' => $payload['Type'] ?? 'unknown']);

        // Handle SNS subscription confirmation
        if (($payload['Type'] ?? '') === 'SubscriptionConfirmation') {
            return $this->handleSubscriptionConfirmation($payload);
        }

        // Handle notifications
        if (($payload['Type'] ?? '') === 'Notification') {
            return $this->handleNotification($payload);
        }

        return response()->json(['status' => 'ignored']);
    }

    /**
     * Handle SNS subscription confirmation.
     */
    protected function handleSubscriptionConfirmation(array $payload): JsonResponse
    {
        $subscribeUrl = $payload['SubscribeURL'] ?? null;

        if ($subscribeUrl) {
            // Automatically confirm the subscription
            try {
                file_get_contents($subscribeUrl);
                Log::info('SNS subscription confirmed', ['url' => $subscribeUrl]);
            } catch (\Exception $e) {
                Log::error('Failed to confirm SNS subscription', ['error' => $e->getMessage()]);
            }
        }

        return response()->json(['status' => 'confirmed']);
    }

    /**
     * Handle SNS notification.
     */
    protected function handleNotification(array $payload): JsonResponse
    {
        $message = json_decode($payload['Message'] ?? '{}', true);

        if (empty($message)) {
            return response()->json(['status' => 'invalid_message'], 400);
        }

        $eventType = $message['eventType'] ?? $message['notificationType'] ?? null;

        Log::info('Processing SES event', ['type' => $eventType]);

        match ($eventType) {
            'Delivery' => $this->handleDelivery($message),
            'Bounce' => $this->handleBounce($message),
            'Complaint' => $this->handleComplaint($message),
            'Open' => $this->handleOpen($message),
            'Click' => $this->handleClick($message),
            'Reject' => $this->handleReject($message),
            'Send' => $this->handleSend($message),
            default => Log::info('Unhandled SES event type', ['type' => $eventType]),
        };

        return response()->json(['status' => 'processed']);
    }

    /**
     * Handle delivery event.
     */
    protected function handleDelivery(array $message): void
    {
        $mail = $message['mail'] ?? [];
        $delivery = $message['delivery'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        if (!$messageId) {
            return;
        }

        $sentEmail = SentEmail::where('message_id', $messageId)->first();

        if (!$sentEmail) {
            Log::warning('SentEmail not found for delivery', ['message_id' => $messageId]);
            return;
        }

        $sentEmail->markAsDelivered();

        // Record event
        EmailEvent::create([
            'tenant_id' => $sentEmail->tenant_id,
            'sent_email_id' => $sentEmail->id,
            'contact_id' => $sentEmail->contact_id,
            'campaign_id' => $sentEmail->campaign_id,
            'event_type' => EmailEvent::TYPE_DELIVERY,
            'message_id' => $messageId,
            'event_at' => now(),
            'raw_data' => $delivery,
        ]);

        // Update campaign stats
        if ($sentEmail->campaign_id) {
            $sentEmail->campaign->incrementStat('delivered');
        }
    }

    /**
     * Handle bounce event.
     */
    protected function handleBounce(array $message): void
    {
        $mail = $message['mail'] ?? [];
        $bounce = $message['bounce'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        if (!$messageId) {
            return;
        }

        $sentEmail = SentEmail::where('message_id', $messageId)->first();

        if (!$sentEmail) {
            Log::warning('SentEmail not found for bounce', ['message_id' => $messageId]);
            return;
        }

        $bounceType = $bounce['bounceType'] ?? 'Unknown';
        $bounceSubType = $bounce['bounceSubType'] ?? null;

        $sentEmail->markAsBounced($bounceType, $bounceSubType);

        // Record event
        EmailEvent::create([
            'tenant_id' => $sentEmail->tenant_id,
            'sent_email_id' => $sentEmail->id,
            'contact_id' => $sentEmail->contact_id,
            'campaign_id' => $sentEmail->campaign_id,
            'event_type' => EmailEvent::TYPE_BOUNCE,
            'message_id' => $messageId,
            'bounce_type' => $bounceType,
            'bounce_subtype' => $bounceSubType,
            'event_at' => now(),
            'raw_data' => $bounce,
        ]);

        // Update contact status for hard bounces
        if ($bounceType === 'Permanent' && $sentEmail->contact_id) {
            $contact = Contact::find($sentEmail->contact_id);
            if ($contact) {
                $contact->update([
                    'status' => Contact::STATUS_BOUNCED,
                    'bounced_at' => now(),
                ]);
            }
        }

        // Update campaign stats
        if ($sentEmail->campaign_id) {
            $sentEmail->campaign->incrementStat('bounced');
        }
    }

    /**
     * Handle complaint event.
     */
    protected function handleComplaint(array $message): void
    {
        $mail = $message['mail'] ?? [];
        $complaint = $message['complaint'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        if (!$messageId) {
            return;
        }

        $sentEmail = SentEmail::where('message_id', $messageId)->first();

        if (!$sentEmail) {
            Log::warning('SentEmail not found for complaint', ['message_id' => $messageId]);
            return;
        }

        $sentEmail->update([
            'status' => SentEmail::STATUS_COMPLAINED,
            'complained_at' => now(),
        ]);

        // Record event
        EmailEvent::create([
            'tenant_id' => $sentEmail->tenant_id,
            'sent_email_id' => $sentEmail->id,
            'contact_id' => $sentEmail->contact_id,
            'campaign_id' => $sentEmail->campaign_id,
            'event_type' => EmailEvent::TYPE_COMPLAINT,
            'message_id' => $messageId,
            'event_at' => now(),
            'raw_data' => $complaint,
        ]);

        // Update contact status
        if ($sentEmail->contact_id) {
            $contact = Contact::find($sentEmail->contact_id);
            if ($contact) {
                $contact->update([
                    'status' => Contact::STATUS_COMPLAINED,
                    'complained_at' => now(),
                ]);
            }
        }

        // Update campaign stats
        if ($sentEmail->campaign_id) {
            $sentEmail->campaign->incrementStat('complained');
        }
    }

    /**
     * Handle open event.
     */
    protected function handleOpen(array $message): void
    {
        $mail = $message['mail'] ?? [];
        $open = $message['open'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        if (!$messageId) {
            return;
        }

        $sentEmail = SentEmail::where('message_id', $messageId)->first();

        if (!$sentEmail) {
            return;
        }

        // Only record first open on sent_email
        if (!$sentEmail->opened_at) {
            $sentEmail->markAsOpened();

            // Update campaign stats (unique opens)
            if ($sentEmail->campaign_id) {
                $sentEmail->campaign->incrementStat('opened');
            }
        }

        // Record event (all opens)
        EmailEvent::create([
            'tenant_id' => $sentEmail->tenant_id,
            'sent_email_id' => $sentEmail->id,
            'contact_id' => $sentEmail->contact_id,
            'campaign_id' => $sentEmail->campaign_id,
            'event_type' => EmailEvent::TYPE_OPEN,
            'message_id' => $messageId,
            'ip_address' => $open['ipAddress'] ?? null,
            'user_agent' => $open['userAgent'] ?? null,
            'event_at' => now(),
            'raw_data' => $open,
        ]);
    }

    /**
     * Handle click event.
     */
    protected function handleClick(array $message): void
    {
        $mail = $message['mail'] ?? [];
        $click = $message['click'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        if (!$messageId) {
            return;
        }

        $sentEmail = SentEmail::where('message_id', $messageId)->first();

        if (!$sentEmail) {
            return;
        }

        // Only record first click on sent_email
        if (!$sentEmail->clicked_at) {
            $sentEmail->markAsClicked();

            // Update campaign stats (unique clicks)
            if ($sentEmail->campaign_id) {
                $sentEmail->campaign->incrementStat('clicked');
            }
        }

        // Record event (all clicks)
        EmailEvent::create([
            'tenant_id' => $sentEmail->tenant_id,
            'sent_email_id' => $sentEmail->id,
            'contact_id' => $sentEmail->contact_id,
            'campaign_id' => $sentEmail->campaign_id,
            'event_type' => EmailEvent::TYPE_CLICK,
            'message_id' => $messageId,
            'link_url' => $click['link'] ?? null,
            'ip_address' => $click['ipAddress'] ?? null,
            'user_agent' => $click['userAgent'] ?? null,
            'event_at' => now(),
            'raw_data' => $click,
        ]);
    }

    /**
     * Handle reject event.
     */
    protected function handleReject(array $message): void
    {
        $mail = $message['mail'] ?? [];
        $reject = $message['reject'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        if (!$messageId) {
            return;
        }

        $sentEmail = SentEmail::where('message_id', $messageId)->first();

        if (!$sentEmail) {
            return;
        }

        $sentEmail->markAsFailed($reject['reason'] ?? 'Rejected by SES');
    }

    /**
     * Handle send event.
     */
    protected function handleSend(array $message): void
    {
        // Send events are already handled when we call SES
        // This is just for logging/tracking purposes
        $mail = $message['mail'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        Log::info('SES Send event confirmed', ['message_id' => $messageId]);
    }
}
