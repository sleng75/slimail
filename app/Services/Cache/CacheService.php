<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache tags for different data types.
     */
    public const TAG_TENANT = 'tenant';
    public const TAG_CONTACTS = 'contacts';
    public const TAG_CAMPAIGNS = 'campaigns';
    public const TAG_TEMPLATES = 'templates';
    public const TAG_STATISTICS = 'statistics';
    public const TAG_SETTINGS = 'settings';

    /**
     * Default TTL values (in seconds).
     */
    public const TTL_SHORT = 300;       // 5 minutes
    public const TTL_MEDIUM = 1800;     // 30 minutes
    public const TTL_LONG = 3600;       // 1 hour
    public const TTL_DAY = 86400;       // 24 hours

    /**
     * Get cached value or execute callback.
     */
    public function remember(string $key, array $tags, int $ttl, callable $callback)
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Cache tenant-specific data.
     */
    public function rememberForTenant(int $tenantId, string $key, callable $callback, int $ttl = self::TTL_MEDIUM)
    {
        $cacheKey = "tenant:{$tenantId}:{$key}";

        return $this->remember(
            $cacheKey,
            [self::TAG_TENANT, "tenant_{$tenantId}"],
            $ttl,
            $callback
        );
    }

    /**
     * Cache dashboard statistics.
     */
    public function cacheDashboardStats(int $tenantId, array $stats): void
    {
        $key = "tenant:{$tenantId}:dashboard_stats";

        Cache::tags([self::TAG_TENANT, self::TAG_STATISTICS, "tenant_{$tenantId}"])
            ->put($key, $stats, self::TTL_SHORT);
    }

    /**
     * Get cached dashboard stats.
     */
    public function getDashboardStats(int $tenantId): ?array
    {
        $key = "tenant:{$tenantId}:dashboard_stats";

        return Cache::tags([self::TAG_TENANT, self::TAG_STATISTICS, "tenant_{$tenantId}"])
            ->get($key);
    }

    /**
     * Cache contact count for a tenant.
     */
    public function cacheContactCount(int $tenantId, int $count): void
    {
        $key = "tenant:{$tenantId}:contact_count";

        Cache::tags([self::TAG_TENANT, self::TAG_CONTACTS, "tenant_{$tenantId}"])
            ->put($key, $count, self::TTL_MEDIUM);
    }

    /**
     * Get cached contact count.
     */
    public function getContactCount(int $tenantId): ?int
    {
        $key = "tenant:{$tenantId}:contact_count";

        return Cache::tags([self::TAG_TENANT, self::TAG_CONTACTS, "tenant_{$tenantId}"])
            ->get($key);
    }

    /**
     * Invalidate all cache for a tenant.
     */
    public function invalidateTenant(int $tenantId): void
    {
        Cache::tags(["tenant_{$tenantId}"])->flush();

        Log::info("Cache invalidated for tenant", ['tenant_id' => $tenantId]);
    }

    /**
     * Invalidate contacts cache for a tenant.
     */
    public function invalidateContacts(int $tenantId): void
    {
        Cache::tags([self::TAG_CONTACTS, "tenant_{$tenantId}"])->flush();
    }

    /**
     * Invalidate campaigns cache for a tenant.
     */
    public function invalidateCampaigns(int $tenantId): void
    {
        Cache::tags([self::TAG_CAMPAIGNS, "tenant_{$tenantId}"])->flush();
    }

    /**
     * Invalidate statistics cache for a tenant.
     */
    public function invalidateStatistics(int $tenantId): void
    {
        Cache::tags([self::TAG_STATISTICS, "tenant_{$tenantId}"])->flush();
    }

    /**
     * Cache email template.
     */
    public function cacheTemplate(int $tenantId, int $templateId, array $template): void
    {
        $key = "tenant:{$tenantId}:template:{$templateId}";

        Cache::tags([self::TAG_TENANT, self::TAG_TEMPLATES, "tenant_{$tenantId}"])
            ->put($key, $template, self::TTL_LONG);
    }

    /**
     * Get cached template.
     */
    public function getTemplate(int $tenantId, int $templateId): ?array
    {
        $key = "tenant:{$tenantId}:template:{$templateId}";

        return Cache::tags([self::TAG_TENANT, self::TAG_TEMPLATES, "tenant_{$tenantId}"])
            ->get($key);
    }

    /**
     * Cache global platform statistics (for admin).
     */
    public function cacheGlobalStats(array $stats): void
    {
        Cache::put('global:platform_stats', $stats, self::TTL_SHORT);
    }

    /**
     * Get cached global stats.
     */
    public function getGlobalStats(): ?array
    {
        return Cache::get('global:platform_stats');
    }

    /**
     * Cache SES reputation data.
     */
    public function cacheSesReputation(array $data): void
    {
        Cache::put('ses:reputation', $data, self::TTL_MEDIUM);
    }

    /**
     * Get cached SES reputation.
     */
    public function getSesReputation(): ?array
    {
        return Cache::get('ses:reputation');
    }

    /**
     * Flush all application caches.
     */
    public function flushAll(): void
    {
        Cache::tags([
            self::TAG_TENANT,
            self::TAG_CONTACTS,
            self::TAG_CAMPAIGNS,
            self::TAG_TEMPLATES,
            self::TAG_STATISTICS,
            self::TAG_SETTINGS,
        ])->flush();

        Cache::forget('global:platform_stats');
        Cache::forget('ses:reputation');

        Log::info("All application caches flushed");
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        $store = Cache::getStore();

        // This works for Redis
        if (method_exists($store, 'getRedis')) {
            $redis = $store->getRedis();
            $info = $redis->info();

            return [
                'driver' => 'redis',
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'keys' => $redis->dbSize(),
                'uptime' => $info['uptime_in_seconds'] ?? 0,
            ];
        }

        return [
            'driver' => config('cache.default'),
            'message' => 'Detailed stats only available for Redis',
        ];
    }
}
