<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

beforeEach(function () {
    // Clear cache before each test
    app(TenantCache::class)->flush();
});

it('caches tenant resolution by domain', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $cache = app(TenantCache::class);

    // First call should hit database
    $resolved1 = $cache->getTenantByDomain('example.test');
    expect($resolved1)->not()->toBeNull();
    expect($resolved1->id)->toBe($tenant->id);

    // Second call should hit cache (we can't easily test this without mocking,
    // but we can verify the result is consistent)
    $resolved2 = $cache->getTenantByDomain('example.test');
    expect($resolved2->id)->toBe($tenant->id);
});

it('caches tenant resolution by slug', function () {
    $tenant = Tenant::factory()->create([
        'slug' => 'acme',
    ]);

    $cache = app(TenantCache::class);

    $resolved1 = $cache->getTenantBySlug('acme');
    expect($resolved1)->not()->toBeNull();
    expect($resolved1->id)->toBe($tenant->id);

    $resolved2 = $cache->getTenantBySlug('acme');
    expect($resolved2->id)->toBe($tenant->id);
});

it('returns null when tenant not found by domain', function () {
    $cache = app(TenantCache::class);

    $resolved = $cache->getTenantByDomain('nonexistent.test');
    expect($resolved)->toBeNull();
});

it('returns null when tenant not found by slug', function () {
    $cache = app(TenantCache::class);

    $resolved = $cache->getTenantBySlug('nonexistent');
    expect($resolved)->toBeNull();
});

it('works when caching is disabled', function () {
    config(['singledb-tenancy.caching.enabled' => false]);

    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $cache = app(TenantCache::class);

    $resolved = $cache->getTenantByDomain('example.test');
    expect($resolved)->not()->toBeNull();
    expect($resolved->id)->toBe($tenant->id);
});

it('forgets specific tenant cache', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
        'slug' => 'example',
    ]);

    $cache = app(TenantCache::class);

    // Cache the tenant
    $cache->getTenantByDomain('example.test');
    $cache->getTenantBySlug('example');

    // Forget the tenant
    $cache->forgetTenant($tenant);

    // Should still resolve (from database)
    $resolved = $cache->getTenantByDomain('example.test');
    expect($resolved)->not()->toBeNull();
});

it('checks custom route file existence', function () {
    // Create a temporary test routes directory
    $routesPath = storage_path('framework/testing/tenant-routes');
    if (! is_dir($routesPath)) {
        mkdir($routesPath, 0755, true);
    }

    config(['singledb-tenancy.routing.custom_routes_path' => $routesPath]);

    $cache = app(TenantCache::class);

    // Should return false when file doesn't exist
    expect($cache->tenantHasCustomRoutes('nonexistent'))->toBeFalse();

    // Create a test route file
    file_put_contents("{$routesPath}/testslug.php", "<?php\n// Test route file\n");

    // Should return true when file exists
    expect($cache->tenantHasCustomRoutes('testslug'))->toBeTrue();

    // Clean up
    unlink("{$routesPath}/testslug.php");
    rmdir($routesPath);
});
