<?php

namespace Roberts\LaravelSingledbTenancy\Commands;

use Illuminate\Console\Command;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

class TenantCacheClearCommand extends Command
{
    public $signature = 'tenancy:cache:clear 
                        {--tenant= : Clear cache for specific tenant domain}
                        {--all : Clear all tenant cache entries}';

    public $description = 'Clear tenant resolution cache';

    public function handle(): int
    {
        $cache = app(TenantCache::class);

        /** @var string|null $tenantOption */
        $tenantOption = $this->option('tenant');
        if ($tenantOption !== null) {
            if (empty($tenantOption)) {
                $this->error('Tenant option must be a non-empty string');

                return self::FAILURE;
            }

            return $this->clearTenantSpecific($cache, $tenantOption);
        }

        if ($this->option('all')) {
            return $this->clearAllCache($cache);
        }

        // Default: clear all tenant cache using tags
        return $this->clearTenantCache($cache);
    }

    protected function clearTenantCache(TenantCache $cache): int
    {
        $this->info('Clearing tenant resolution cache...');

        try {
            $cache->flush();
            $this->info('✓ Tenant cache cleared successfully');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clear tenant cache: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function clearAllCache(TenantCache $cache): int
    {
        $this->info('Clearing all tenant-related cache entries...');

        try {
            $cache->flushAll();
            $this->info('✓ All tenant cache cleared successfully');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clear all tenant cache: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function clearTenantSpecific(TenantCache $cache, string $domain): int
    {
        $this->info("Clearing cache for tenant: {$domain}");

        try {
            $cache->forgetTenantByDomain($domain);

            // Always show success message since we attempted to clear
            $this->info("✓ Cache cleared for tenant: {$domain}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clear cache for tenant {$domain}: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
