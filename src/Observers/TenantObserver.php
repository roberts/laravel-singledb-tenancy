<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Observers;

use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

class TenantObserver
{
    public function __construct(
        private TenantCache $tenantCache
    ) {}

    /**
     * Handle the tenant "created" event.
     */
    public function created(Tenant $tenant): void
    {
        // When a tenant is created, we don't need to invalidate the existence cache
        // since it will be permanently cached once true
        
        // Clear specific tenant caches if they exist
        $this->tenantCache->forgetTenant($tenant);
    }

    /**
     * Handle the tenant "updated" event.
     */
    public function updated(Tenant $tenant): void
    {
        // Clear cached data for this tenant
        $this->tenantCache->forgetTenant($tenant);
    }

    /**
     * Handle the tenant "deleted" event.
     */
    public function deleted(Tenant $tenant): void
    {
        // Clear cached data for this tenant
        $this->tenantCache->forgetTenant($tenant);
        
        // If this isn't tenant ID 1, invalidate existence cache
        // (though tenant ID 1 can't be deleted due to boot protection)
        if ($tenant->id !== 1) {
            $this->tenantCache->invalidateExistenceCache();
        }
    }

    /**
     * Handle the tenant "force deleted" event.
     */
    public function forceDeleted(Tenant $tenant): void
    {
        // Same as deleted
        $this->deleted($tenant);
    }
}
