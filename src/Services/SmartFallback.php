<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class SmartFallback
{
    public const CACHE_KEY = 'tenancy.smart_fallback.tenants_exist';

    /**
     * Determines if the application should be in a "fallback" state.
     *
     * It checks a permanent cache flag. If the flag is not set, it performs
     * a live database check. If tenants are found, it sets the flag
     * permanently to avoid future database checks.
     */
    public function isFallback(): bool
    {
        // If we've already confirmed that tenants exist, no need to check again.
        if (Cache::get(self::CACHE_KEY) === true) {
            return false; // Fallback is NOT active.
        }

        // If the cache is not set, check the database.
        if ($this->tenantsExist()) {
            // Tenants exist, so cache this fact forever and disable fallback.
            $this->permanentlyCacheTenantsExist();

            return false;
        }

        // No tenants exist, so fallback IS active.
        return true;
    }

    /**
     * Permanently cache that tenants exist in the database.
     */
    public function permanentlyCacheTenantsExist(): void
    {
        Cache::forever(self::CACHE_KEY, true);
    }

    /**
     * Check if the tenants table exists and has any records.
     */
    protected function tenantsExist(): bool
    {
        // Avoid errors if migrations haven't been run yet.
        if (! Schema::hasTable('tenants')) {
            return false;
        }

        return Tenant::query()->exists();
    }
}
