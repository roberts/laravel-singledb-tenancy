<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Resolvers;

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

class SubdomainResolver
{
    public function __construct(
        private TenantCache $cache
    ) {}

    /**
     * Resolve tenant by request subdomain.
     */
    public function resolve(Request $request): ?Tenant
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $subdomain = $this->extractSubdomain($request);

        if (! $subdomain) {
            return null;
        }

        if ($this->isReservedSubdomain($subdomain)) {
            return null;
        }

        return $this->cache->getTenantBySlug($subdomain);
    }

    /**
     * Extract subdomain from request.
     */
    protected function extractSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $baseDomain = $this->getBaseDomain();

        if (! $host || ! $baseDomain) {
            return null;
        }

        // Remove the base domain to get subdomain
        $baseDomainWithDot = ".{$baseDomain}";

        if (! str_ends_with($host, $baseDomainWithDot)) {
            return null;
        }

        $subdomain = str_replace($baseDomainWithDot, '', $host);

        // Ensure we have a subdomain and it's not empty
        if (empty($subdomain) || $subdomain === $baseDomain) {
            return null;
        }

        // For simplicity, we only handle single-level subdomains
        // e.g., "tenant1.example.com" but not "api.tenant1.example.com"
        if (str_contains($subdomain, '.')) {
            return null;
        }

        return $subdomain;
    }

    /**
     * Check if subdomain is reserved and should not resolve to a tenant.
     */
    protected function isReservedSubdomain(string $subdomain): bool
    {
        $reserved = $this->getReservedSubdomains();

        return in_array($subdomain, $reserved, true);
    }

    /**
     * Get base domain from configuration.
     */
    protected function getBaseDomain(): string
    {
        return config('singledb-tenancy.resolution.subdomain.base_domain', 'localhost');
    }

    /**
     * Get reserved subdomains from configuration.
     */
    protected function getReservedSubdomains(): array
    {
        return config('singledb-tenancy.resolution.subdomain.reserved', []);
    }

    /**
     * Check if subdomain resolution is enabled.
     */
    protected function isEnabled(): bool
    {
        return config('singledb-tenancy.resolution.subdomain.enabled', true);
    }
}
