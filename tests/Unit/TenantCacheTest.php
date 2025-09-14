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

it('returns null when tenant not found by domain', function () {
    $cache = app(TenantCache::class);

    $resolved = $cache->getTenantByDomain('nonexistent.test');
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
    ]);

    $cache = app(TenantCache::class);

    // Cache the tenant
    $cache->getTenantByDomain('example.test');

    // Forget the tenant
    $cache->forgetTenant($tenant);

    // Should still resolve (from database)
    $resolved = $cache->getTenantByDomain('example.test');
    expect($resolved)->not()->toBeNull();
});

it('checks custom route file existence', function () {
    // Disable caching for this test to avoid cache pollution
    config(['singledb-tenancy.caching.enabled' => false]);

    // Create a temporary test routes directory
    $routesPath = storage_path('framework/testing/tenant-routes');
    if (! is_dir($routesPath)) {
        mkdir($routesPath, 0755, true);
    }

    // Clean up any existing files first
    $filePath = "{$routesPath}/example.com.php";
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    config(['singledb-tenancy.routing.custom_routes_path' => $routesPath]);

    $cache = app(TenantCache::class);

    // Verify file doesn't exist
    expect(file_exists($filePath))->toBeFalse();

    // Should return false when file doesn't exist
    expect($cache->tenantHasCustomRoutes('example.com'))->toBeFalse();

    // Create a test route file using tenant domain
    file_put_contents($filePath, "<?php\n// Test route file\n");

    // Debug: verify file was created
    expect(file_exists($filePath))->toBeTrue();
    expect(config('singledb-tenancy.routing.custom_routes_path'))->toBe($routesPath);

    // Should return true when file exists
    expect($cache->tenantHasCustomRoutes('example.com'))->toBeTrue();

    // Clean up
    unlink($filePath);
    if (is_dir($routesPath)) {
        rmdir($routesPath);
    }
});
