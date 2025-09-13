<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Services;

use Illuminate\Cache\CacheManager;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class TenantCache
{
    public function __construct(
        private CacheManager $cache
    ) {}

    /**
     * Get tenant by domain from cache or database.
     */
    public function getTenantByDomain(string $domain): ?Tenant
    {
        if (! $this->isCacheEnabled()) {
            return $this->resolveTenantByDomain($domain);
        }

        $key = $this->getDomainCacheKey($domain);

        return $this->getCache()->remember($key, $this->getCacheTtl(), function () use ($domain) {
            return $this->resolveTenantByDomain($domain);
        });
    }

    /**
     * Get tenant by slug from cache or database.
     */
    public function getTenantBySlug(string $slug): ?Tenant
    {
        if (! $this->isCacheEnabled()) {
            return $this->resolveTenantBySlug($slug);
        }

        $key = $this->getSlugCacheKey($slug);

        return $this->getCache()->remember($key, $this->getCacheTtl(), function () use ($slug) {
            return $this->resolveTenantBySlug($slug);
        });
    }

    /**
     * Check if tenant has custom route file.
     */
    public function tenantHasCustomRoutes(string $identifier): bool
    {
        if (! $this->isCacheEnabled()) {
            return $this->checkCustomRouteFile($identifier);
        }

        $key = $this->getCustomRoutesCacheKey($identifier);

        return $this->getCache()->remember($key, $this->getCacheTtl(), function () use ($identifier) {
            return $this->checkCustomRouteFile($identifier);
        });
    }

    /**
     * Invalidate all tenant resolution cache.
     * Note: This only works with cache drivers that support tags (Redis, Memcached).
     */
    public function flush(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        // For cache drivers that don't support tags, cache will expire naturally
        // This method is primarily for manual cache clearing during development
    }

    /**
     * Invalidate cache for a specific tenant.
     */
    public function forgetTenant(Tenant $tenant): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $cache = $this->getCache();

        // Clear domain cache
        if ($tenant->domain) {
            $cache->forget($this->getDomainCacheKey($tenant->domain));
        }

        // Clear slug cache
        if ($tenant->slug) {
            $cache->forget($this->getSlugCacheKey($tenant->slug));
            $cache->forget($this->getCustomRoutesCacheKey($tenant->slug));
        }
    }

    /**
     * Resolve tenant by domain from database.
     */
    protected function resolveTenantByDomain(string $domain): ?Tenant
    {
        /** @var class-string<Tenant> $tenantModel */
        $tenantModel = config('singledb-tenancy.tenant_model');
        $column = config('singledb-tenancy.resolution.domain.column', 'domain');

        return $tenantModel::where($column, $domain)->first();
    }

    /**
     * Resolve tenant by slug from database.
     */
    protected function resolveTenantBySlug(string $slug): ?Tenant
    {
        /** @var class-string<Tenant> $tenantModel */
        $tenantModel = config('singledb-tenancy.tenant_model');
        $column = config('singledb-tenancy.resolution.subdomain.column', 'slug');

        return $tenantModel::where($column, $slug)->first();
    }

    /**
     * Check if custom route file exists for tenant.
     */
    protected function checkCustomRouteFile(string $identifier): bool
    {
        $routesPath = config('singledb-tenancy.routing.custom_routes_path');
        $filename = "{$identifier}.php";

        return file_exists("{$routesPath}/{$filename}");
    }

    /**
     * Get cache instance.
     */
    protected function getCache()
    {
        $store = config('singledb-tenancy.caching.store', 'array'); // Use array as fallback for testing

        return $this->cache->store($store);
    }

    /**
     * Check if caching is enabled.
     */
    protected function isCacheEnabled(): bool
    {
        return config('singledb-tenancy.caching.enabled', true);
    }

    /**
     * Get cache TTL in seconds.
     */
    protected function getCacheTtl(): int
    {
        return config('singledb-tenancy.caching.ttl', 3600);
    }

    /**
     * Get cache tags.
     */
    protected function getCacheTags(): array
    {
        return config('singledb-tenancy.caching.tags', ['tenant_resolution']);
    }

    /**
     * Get cache key for domain resolution.
     */
    protected function getDomainCacheKey(string $domain): string
    {
        $prefix = config('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}domain:{$domain}";
    }

    /**
     * Get cache key for slug resolution.
     */
    protected function getSlugCacheKey(string $slug): string
    {
        $prefix = config('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}slug:{$slug}";
    }

    /**
     * Get cache key for custom routes check.
     */
    protected function getCustomRoutesCacheKey(string $identifier): string
    {
        $prefix = config('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}routes:{$identifier}";
    }
}
