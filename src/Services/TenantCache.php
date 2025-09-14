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
        $columnStr = is_string($column) ? $column : 'domain';

        return $tenantModel::where($columnStr, $domain)->first();
    }

    /**
     * Resolve tenant by slug from database.
     */
    protected function resolveTenantBySlug(string $slug): ?Tenant
    {
        /** @var class-string<Tenant> $tenantModel */
        $tenantModel = config('singledb-tenancy.tenant_model');
        $column = config('singledb-tenancy.resolution.subdomain.column', 'slug');
        $columnStr = is_string($column) ? $column : 'slug';

        return $tenantModel::where($columnStr, $slug)->first();
    }

    /**
     * Check if custom route file exists for tenant.
     */
    protected function checkCustomRouteFile(string $identifier): bool
    {
        $routesPath = config('singledb-tenancy.routing.custom_routes_path', '');
        $routesPathStr = is_string($routesPath) ? $routesPath : '';
        $filename = "{$identifier}.php";

        if (empty($routesPathStr)) {
            return false;
        }

        return file_exists("{$routesPathStr}/{$filename}");
    }

    /**
     * Get cache instance.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function getCache()
    {
        $store = config('singledb-tenancy.caching.store', 'array');
        $storeStr = is_string($store) ? $store : 'array';

        return $this->cache->store($storeStr);
    }

    /**
     * Check if caching is enabled.
     */
    protected function isCacheEnabled(): bool
    {
        return (bool) config('singledb-tenancy.caching.enabled', true);
    }

    /**
     * Get cache TTL in seconds.
     */
    protected function getCacheTtl(): int
    {
        $ttl = config('singledb-tenancy.caching.ttl', 3600);
        return is_int($ttl) ? $ttl : 3600;
    }

    /**
     * Get cache tags.
     *
     * @return array<string>
     */
    protected function getCacheTags(): array
    {
        $tags = config('singledb-tenancy.caching.tags', ['tenant_resolution']);
        
        if (! is_array($tags)) {
            return ['tenant_resolution'];
        }
        
        /** @var array<string> */
        return array_filter($tags, 'is_string');
    }

    /**
     * Get config value as string with fallback.
     */
    private function getConfigString(string $key, string $default = ''): string
    {
        $value = config($key, $default);
        return is_string($value) ? $value : $default;
    }

    /**
     * Get cache key for domain resolution.
     */
    protected function getDomainCacheKey(string $domain): string
    {
        $prefix = $this->getConfigString('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}domain:{$domain}";
    }

    /**
     * Check if any tenants exist (permanently cached once true).
     */
    public function tenantsExist(): bool
    {
        if (! $this->isCacheEnabled()) {
            return $this->resolveTenantsExist();
        }

        $key = $this->getTenantExistenceKey();

        // Check cache first
        $cached = $this->getCache()->get($key);
        if ($cached === true) {
            return true;
        }

        // If not cached or false, check database
        $exists = $this->resolveTenantsExist();
        
        // If tenants exist, cache permanently (no TTL)
        if ($exists) {
            $this->getCache()->forever($key, true);
        }

        return $exists;
    }

    /**
     * Check if tenant ID 1 exists (permanently cached once true).
     */
    public function primaryTenantExists(): bool
    {
        if (! $this->isCacheEnabled()) {
            return $this->resolvePrimaryTenantExists();
        }

        $key = $this->getPrimaryTenantKey();

        // Check cache first
        $cached = $this->getCache()->get($key);
        if ($cached === true) {
            return true;
        }

        // If not cached or false, check database
        $exists = $this->resolvePrimaryTenantExists();
        
        // If tenant 1 exists, cache permanently (no TTL)
        if ($exists) {
            $this->getCache()->forever($key, true);
        }

        return $exists;
    }

    /**
     * Get primary tenant (ID 1) from cache or database.
     */
    public function getPrimaryTenant(): ?Tenant
    {
        if (! $this->isCacheEnabled()) {
            return $this->resolvePrimaryTenant();
        }

        $key = $this->getPrimaryTenantModelKey();

        return $this->getCache()->remember($key, $this->getCacheTtl(), function () {
            return $this->resolvePrimaryTenant();
        });
    }

    /**
     * Invalidate tenant existence cache (called when tenants are deleted).
     */
    public function invalidateExistenceCache(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $this->getCache()->forget($this->getTenantExistenceKey());
        
        // Note: We don't invalidate primary tenant cache since it can't be deleted
    }

    /**
     * Get cache key for slug resolution.
     */
    protected function getSlugCacheKey(string $slug): string
    {
        $prefix = $this->getConfigString('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}slug:{$slug}";
    }

    /**
     * Get cache key for custom routes check.
     */
    protected function getCustomRoutesCacheKey(string $identifier): string
    {
        $prefix = $this->getConfigString('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}routes:{$identifier}";
    }

    /**
     * Get cache key for tenant existence check.
     */
    protected function getTenantExistenceKey(): string
    {
        $prefix = $this->getConfigString('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}tenants_exist";
    }

    /**
     * Get cache key for primary tenant existence check.
     */
    protected function getPrimaryTenantKey(): string
    {
        $prefix = $this->getConfigString('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}primary_tenant_exists";
    }

    /**
     * Get cache key for primary tenant model.
     */
    protected function getPrimaryTenantModelKey(): string
    {
        $prefix = $this->getConfigString('singledb-tenancy.caching.key_prefix', 'tenant_resolution:');

        return "{$prefix}primary_tenant_model";
    }

    /**
     * Resolve if any tenants exist from database.
     */
    protected function resolveTenantsExist(): bool
    {
        $tenantModel = config('singledb-tenancy.tenant_model', Tenant::class);

        if (is_string($tenantModel) && class_exists($tenantModel)) {
            return $tenantModel::exists();
        }
        
        return false;
    }

    /**
     * Resolve if primary tenant exists from database.
     */
    protected function resolvePrimaryTenantExists(): bool
    {
        $tenantModel = config('singledb-tenancy.tenant_model', Tenant::class);

        if (is_string($tenantModel) && class_exists($tenantModel)) {
            return $tenantModel::where('id', 1)->exists();
        }
        
        return false;
    }

    /**
     * Resolve primary tenant from database.
     */
    protected function resolvePrimaryTenant(): ?Tenant
    {
        $tenantModel = config('singledb-tenancy.tenant_model', Tenant::class);

        if (is_string($tenantModel) && class_exists($tenantModel)) {
            return $tenantModel::find(1);
        }
        
        return null;
    }
}
