<?php

namespace App\Services\Cache;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QueryOptimizer
{
    /**
     * Optimize a contacts query with proper indexing hints.
     */
    public function optimizeContactsQuery(Builder $query, int $tenantId): Builder
    {
        return $query
            ->where('tenant_id', $tenantId)
            ->select([
                'id',
                'email',
                'first_name',
                'last_name',
                'status',
                'created_at',
                'engagement_score',
            ]);
    }

    /**
     * Get contact count efficiently using count query.
     */
    public function getContactCountOptimized(int $tenantId, ?string $status = null): int
    {
        $query = DB::table('contacts')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    /**
     * Get campaign statistics efficiently.
     */
    public function getCampaignStats(int $campaignId): array
    {
        return DB::table('sent_emails')
            ->where('campaign_id', $campaignId)
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN status = "complained" THEN 1 ELSE 0 END) as complained,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked
            ')
            ->first()
            ?->toArray() ?? [];
    }

    /**
     * Get email volume by date range efficiently.
     */
    public function getEmailVolumeByDate(int $tenantId, string $startDate, string $endDate): array
    {
        return DB::table('sent_emails')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get tenant statistics efficiently using raw queries.
     */
    public function getTenantStats(int $tenantId): array
    {
        $stats = DB::selectOne('
            SELECT
                (SELECT COUNT(*) FROM contacts WHERE tenant_id = ? AND deleted_at IS NULL) as total_contacts,
                (SELECT COUNT(*) FROM contacts WHERE tenant_id = ? AND deleted_at IS NULL AND status = "subscribed") as subscribed_contacts,
                (SELECT COUNT(*) FROM campaigns WHERE tenant_id = ? AND deleted_at IS NULL) as total_campaigns,
                (SELECT COUNT(*) FROM campaigns WHERE tenant_id = ? AND deleted_at IS NULL AND status = "sent") as sent_campaigns,
                (SELECT COUNT(*) FROM sent_emails WHERE tenant_id = ?) as total_emails,
                (SELECT COUNT(*) FROM sent_emails WHERE tenant_id = ? AND status = "delivered") as delivered_emails,
                (SELECT COUNT(*) FROM email_templates WHERE tenant_id = ? AND deleted_at IS NULL) as total_templates
        ', [$tenantId, $tenantId, $tenantId, $tenantId, $tenantId, $tenantId, $tenantId]);

        return (array) $stats;
    }

    /**
     * Get top senders (tenants with most emails sent).
     */
    public function getTopSenders(int $days = 30, int $limit = 10): array
    {
        return DB::table('sent_emails')
            ->join('tenants', 'sent_emails.tenant_id', '=', 'tenants.id')
            ->where('sent_emails.created_at', '>=', now()->subDays($days))
            ->groupBy('tenants.id', 'tenants.name')
            ->select([
                'tenants.id',
                'tenants.name',
                DB::raw('COUNT(*) as sent_emails_count'),
            ])
            ->orderByDesc('sent_emails_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get bounce rate for a tenant.
     */
    public function getTenantBounceRate(int $tenantId, int $days = 30): float
    {
        $result = DB::selectOne('
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced
            FROM sent_emails
            WHERE tenant_id = ?
            AND created_at >= ?
        ', [$tenantId, now()->subDays($days)]);

        if ($result->total === 0) {
            return 0;
        }

        return round(($result->bounced / $result->total) * 100, 2);
    }

    /**
     * Get automation enrollment stats efficiently.
     */
    public function getAutomationEnrollmentStats(int $automationId): array
    {
        return DB::table('automation_enrollments')
            ->where('automation_id', $automationId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "exited" THEN 1 ELSE 0 END) as exited,
                SUM(CASE WHEN status = "paused" THEN 1 ELSE 0 END) as paused
            ')
            ->first()
            ?->toArray() ?? [];
    }

    /**
     * Get list members with minimal data for email sending.
     */
    public function getListMembersForSending(int $listId, int $chunkSize = 1000): \Generator
    {
        $lastId = 0;

        while (true) {
            $members = DB::table('contact_list_members')
                ->join('contacts', 'contact_list_members.contact_id', '=', 'contacts.id')
                ->where('contact_list_members.contact_list_id', $listId)
                ->where('contact_list_members.status', 'active')
                ->where('contacts.status', 'subscribed')
                ->whereNull('contacts.deleted_at')
                ->where('contacts.id', '>', $lastId)
                ->orderBy('contacts.id')
                ->limit($chunkSize)
                ->select([
                    'contacts.id',
                    'contacts.email',
                    'contacts.first_name',
                    'contacts.last_name',
                    'contacts.custom_fields',
                ])
                ->get();

            if ($members->isEmpty()) {
                break;
            }

            foreach ($members as $member) {
                $lastId = $member->id;
                yield $member;
            }
        }
    }

    /**
     * Get daily sending quota usage.
     */
    public function getDailySendingCount(int $tenantId): int
    {
        return DB::table('sent_emails')
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get contacts by segment criteria efficiently.
     */
    public function getContactsBySegment(int $tenantId, array $criteria, int $limit = 100): array
    {
        $query = DB::table('contacts')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('status', 'subscribed');

        // Apply segment criteria
        foreach ($criteria as $criterion) {
            $this->applySegmentCriterion($query, $criterion);
        }

        return $query
            ->select(['id', 'email', 'first_name', 'last_name'])
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Apply a single segment criterion to the query.
     */
    protected function applySegmentCriterion($query, array $criterion): void
    {
        $field = $criterion['field'] ?? null;
        $operator = $criterion['operator'] ?? '=';
        $value = $criterion['value'] ?? null;

        if (!$field || $value === null) {
            return;
        }

        // Handle standard fields
        $standardFields = ['email', 'first_name', 'last_name', 'company', 'city', 'country', 'status', 'engagement_score'];

        if (in_array($field, $standardFields)) {
            switch ($operator) {
                case 'equals':
                    $query->where($field, '=', $value);
                    break;
                case 'not_equals':
                    $query->where($field, '!=', $value);
                    break;
                case 'contains':
                    $query->where($field, 'LIKE', "%{$value}%");
                    break;
                case 'not_contains':
                    $query->where($field, 'NOT LIKE', "%{$value}%");
                    break;
                case 'starts_with':
                    $query->where($field, 'LIKE', "{$value}%");
                    break;
                case 'ends_with':
                    $query->where($field, 'LIKE', "%{$value}");
                    break;
                case 'greater_than':
                    $query->where($field, '>', $value);
                    break;
                case 'less_than':
                    $query->where($field, '<', $value);
                    break;
            }
        } else {
            // Handle custom fields (JSON)
            $jsonPath = "custom_fields->'{$field}'";

            switch ($operator) {
                case 'equals':
                    $query->whereJsonContains('custom_fields', [$field => $value]);
                    break;
                case 'not_equals':
                    $query->whereJsonDoesntContain('custom_fields', [$field => $value]);
                    break;
                case 'contains':
                    $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$field}')) LIKE ?", ["%{$value}%"]);
                    break;
            }
        }
    }
}
