<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Services;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Cache;
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

        return $this->rememberWithTags($key, $this->getCacheTtl(), function () use ($domain) {
            return $this->resolveTenantByDomain($domain);
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

        return $this->rememberWithTags($key, $this->getCacheTtl(), function () use ($identifier) {
            return $this->checkCustomRouteFile($identifier);
        });
    }

    /**
     * Invalidate all tenant resolution cache using tags.
     * Note: This only works with cache drivers that support tags (Redis, Memcached).
     */
    public function flush(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $tags = $this->getCacheTags();
        $store = config('singledb-tenancy.caching.store', 'default');
        $storeString = is_string($store) ? $store : 'default';

        try {
            // Use Cache facade for tagged operations (supports Redis/Memcached)
            $cacheStore = $storeString === 'default' ? Cache::store() : Cache::store($storeString);

            // Check if the store supports tagging
            if (method_exists($cacheStore, 'tags')) {
                $taggedCache = $cacheStore->tags($tags);
                $taggedCache->flush();
            } else {
                // Cache driver doesn't support tags, fall back to manual clearing
                $this->clearCacheManually();
            }
        } catch (\BadMethodCallException|\Exception $e) {
            // Cache driver doesn't support tags, fall back to manual clearing
            $this->clearCacheManually();
        }
    }

    /**
     * Clear all tenant-related cache entries (including non-tagged entries).
     */
    public function flushAll(): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        // Clear tagged cache first
        $this->flush();

        // Also clear any manually tracked cache keys
        $this->clearCacheManually();
    }

    /**
     * Forget cache for a specific tenant by domain.
     */
    public function forgetTenantByDomain(string $domain): bool
    {
        if (! $this->isCacheEnabled()) {
            return false;
        }

        $cache = $this->getCache();
        $cleared = false;

        // Clear domain resolution cache
        $domainKey = $this->getDomainCacheKey($domain);
        if ($cache->forget($domainKey)) {
            $cleared = true;
        }

        // Clear custom routes cache
        $customRoutesKey = $this->getCustomRoutesCacheKey($domain);
        if ($cache->forget($customRoutesKey)) {
            $cleared = true;
        }

        // Also try to clear from tagged cache if available
        $this->forgetFromTaggedCache($domainKey);
        $this->forgetFromTaggedCache($customRoutesKey);

        return $cleared;
    }

    /**
     * Attempt to forget a key from tagged cache.
     */
    protected function forgetFromTaggedCache(string $key): void
    {
        $store = config('singledb-tenancy.caching.store', 'default');
        $storeString = is_string($store) ? $store : 'default';
        $tags = $this->getCacheTags();

        try {
            $cacheStore = $storeString === 'default' ? Cache::store() : Cache::store($storeString);

            if (method_exists($cacheStore, 'tags')) {
                $cacheStore->tags($tags)->forget($key);
            }
        } catch (\BadMethodCallException|\Exception $e) {
            // Ignore errors - this is best effort
        }
    }

    /**
     * Manually clear cache keys when tags aren't supported.
     */
    protected function clearCacheManually(): void
    {
        $cache = $this->getCache();

        // Clear tenant existence cache
        $cache->forget($this->getTenantExistenceKey());
        $cache->forget($this->getPrimaryTenantKey());
        $cache->forget($this->getPrimaryTenantModelKey());

        // Note: We can't easily clear all domain/custom route keys without tags
        // These will expire naturally based on TTL
    }

    /**
     * Cache with tags if supported, fallback to regular cache.
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    protected function rememberWithTags(string $key, int $ttl, \Closure $callback)
    {
        $store = config('singledb-tenancy.caching.store', 'default');
        $storeString = is_string($store) ? $store : 'default';
        $tags = $this->getCacheTags();

        try {
            // Use Cache facade for tagged operations (supports Redis/Memcached)
            $cacheStore = $storeString === 'default' ? Cache::store() : Cache::store($storeString);

            // Check if the store supports tagging
            if (method_exists($cacheStore, 'tags')) {
                return $cacheStore->tags($tags)->remember($key, $ttl, $callback);
            } else {
                // Fall back to regular cache
                return $this->getCache()->remember($key, $ttl, $callback);
            }
        } catch (\BadMethodCallException|\Exception $e) {
            // Fall back to regular cache
            return $this->getCache()->remember($key, $ttl, $callback);
        }
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

        // Clear custom routes cache (using tenant domain)
        if ($tenant->domain) {
            $cache->forget($this->getCustomRoutesCacheKey($tenant->domain));
        }
    }

    /**
     * Resolve tenant by domain from database.
     */
    protected function resolveTenantByDomain(string $domain): ?Tenant
    {
        /** @var class-string<Tenant> $tenantModel */
        $tenantModel = config('singledb-tenancy.tenant_model');

        // Use the model's resolveByDomain method which excludes soft-deleted tenants
        return $tenantModel::resolveByDomain($domain);
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

        return $this->rememberWithTags($key, $this->getCacheTtl(), function () {
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
