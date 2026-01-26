<?php

namespace App\Services\Tracking;

use App\Models\EmailEvent;
use App\Models\SentEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackingService
{
    /**
     * Parse user agent to extract device info.
     */
    public function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'device_type' => null,
                'client_name' => null,
                'client_os' => null,
            ];
        }

        $userAgent = strtolower($userAgent);

        // Detect device type
        $deviceType = 'desktop';
        if (preg_match('/(mobile|android|iphone|ipod|blackberry|windows phone)/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/(tablet|ipad|kindle|playbook)/i', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Detect email client
        $clientName = $this->detectEmailClient($userAgent);

        // Detect OS
        $clientOs = $this->detectOS($userAgent);

        return [
            'device_type' => $deviceType,
            'client_name' => $clientName,
            'client_os' => $clientOs,
        ];
    }

    /**
     * Detect email client from user agent.
     */
    protected function detectEmailClient(string $userAgent): string
    {
        $clients = [
            'gmail' => 'Gmail',
            'googleimageproxy' => 'Gmail',
            'outlook' => 'Outlook',
            'microsoft outlook' => 'Outlook',
            'thunderbird' => 'Thunderbird',
            'apple mail' => 'Apple Mail',
            'yahoo' => 'Yahoo Mail',
            'samsung mail' => 'Samsung Mail',
            'airmail' => 'Airmail',
            'spark' => 'Spark',
            'mailspring' => 'Mailspring',
            'edison' => 'Edison Mail',
        ];

        foreach ($clients as $pattern => $name) {
            if (Str::contains($userAgent, $pattern)) {
                return $name;
            }
        }

        // Browser-based clients
        if (Str::contains($userAgent, 'chrome')) {
            return 'Chrome (Web)';
        }
        if (Str::contains($userAgent, 'firefox')) {
            return 'Firefox (Web)';
        }
        if (Str::contains($userAgent, 'safari') && !Str::contains($userAgent, 'chrome')) {
            return 'Safari (Web)';
        }
        if (Str::contains($userAgent, 'edge')) {
            return 'Edge (Web)';
        }

        return 'Unknown';
    }

    /**
     * Detect OS from user agent.
     */
    protected function detectOS(string $userAgent): string
    {
        $osList = [
            'windows nt 10' => 'Windows 10/11',
            'windows nt 6.3' => 'Windows 8.1',
            'windows nt 6.2' => 'Windows 8',
            'windows nt 6.1' => 'Windows 7',
            'macintosh' => 'macOS',
            'mac os x' => 'macOS',
            'iphone os' => 'iOS',
            'ipad' => 'iPadOS',
            'android' => 'Android',
            'linux' => 'Linux',
            'cros' => 'Chrome OS',
        ];

        foreach ($osList as $pattern => $name) {
            if (Str::contains($userAgent, $pattern)) {
                return $name;
            }
        }

        return 'Unknown';
    }

    /**
     * Generate tracking pixel URL.
     */
    public function generatePixelUrl(SentEmail $sentEmail): string
    {
        return route('track.open', ['sentEmailId' => $sentEmail->id]);
    }

    /**
     * Generate tracked link URL.
     */
    public function generateTrackedLink(SentEmail $sentEmail, string $originalUrl): string
    {
        return route('track.click', [
            'sentEmailId' => $sentEmail->id,
            'url' => urlencode($originalUrl),
        ]);
    }

    /**
     * Replace all links in HTML content with tracked links.
     */
    public function injectTrackingLinks(string $html, SentEmail $sentEmail): string
    {
        // Match all href attributes
        $pattern = '/href=["\']([^"\']+)["\']/i';

        return preg_replace_callback($pattern, function ($matches) use ($sentEmail) {
            $originalUrl = $matches[1];

            // Skip special links
            if ($this->shouldSkipLink($originalUrl)) {
                return $matches[0];
            }

            $trackedUrl = $this->generateTrackedLink($sentEmail, $originalUrl);

            return 'href="' . $trackedUrl . '"';
        }, $html);
    }

    /**
     * Check if link should be skipped for tracking.
     */
    protected function shouldSkipLink(string $url): bool
    {
        // Skip mailto links
        if (Str::startsWith($url, 'mailto:')) {
            return true;
        }

        // Skip tel links
        if (Str::startsWith($url, 'tel:')) {
            return true;
        }

        // Skip anchor links
        if (Str::startsWith($url, '#')) {
            return true;
        }

        // Skip unsubscribe links (already tracked)
        if (Str::contains($url, 'unsubscribe')) {
            return true;
        }

        // Skip javascript links
        if (Str::startsWith($url, 'javascript:')) {
            return true;
        }

        return false;
    }

    /**
     * Inject tracking pixel into HTML content.
     */
    public function injectTrackingPixel(string $html, SentEmail $sentEmail): string
    {
        $pixelUrl = $this->generatePixelUrl($sentEmail);
        $pixelTag = '<img src="' . $pixelUrl . '" width="1" height="1" style="display:none;border:0;" alt="" />';

        // Try to inject before </body>
        if (Str::contains($html, '</body>')) {
            return str_replace('</body>', $pixelTag . '</body>', $html);
        }

        // Append at the end
        return $html . $pixelTag;
    }

    /**
     * Prepare HTML content with all tracking.
     */
    public function prepareTrackedContent(string $html, SentEmail $sentEmail): string
    {
        // Inject link tracking
        $html = $this->injectTrackingLinks($html, $sentEmail);

        // Inject pixel tracking
        $html = $this->injectTrackingPixel($html, $sentEmail);

        return $html;
    }

    /**
     * Record an email event with enriched data.
     */
    public function recordEvent(
        SentEmail $sentEmail,
        string $eventType,
        Request $request,
        array $additionalData = []
    ): EmailEvent {
        $userAgentInfo = $this->parseUserAgent($request->userAgent());

        $eventData = array_merge([
            'tenant_id' => $sentEmail->tenant_id,
            'sent_email_id' => $sentEmail->id,
            'contact_id' => $sentEmail->contact_id,
            'event_type' => $eventType,
            'message_id' => $sentEmail->message_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_at' => now(),
            'device_type' => $userAgentInfo['device_type'],
            'client_name' => $userAgentInfo['client_name'],
            'client_os' => $userAgentInfo['client_os'],
        ], $additionalData);

        return EmailEvent::create($eventData);
    }
}
