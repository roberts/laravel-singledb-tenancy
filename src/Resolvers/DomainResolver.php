<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Resolvers;

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

class DomainResolver
{
    public function __construct(
        private TenantCache $cache
    ) {}

    /**
     * Resolve tenant by request domain.
     */
    public function resolve(Request $request): ?Tenant
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $domain = $request->getHost();

        if (! $domain) {
            return null;
        }

        return $this->cache->getTenantByDomain($domain);
    }

    /**
     * Check if domain resolution is enabled.
     */
    protected function isEnabled(): bool
    {
        return (bool) config('singledb-tenancy.resolution.domain.enabled', true);
    }
}
