<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

beforeEach(function () {
    $this->tenantCache = app(TenantCache::class);
});

it('prevents deletion of tenant ID 1', function () {
    $tenant = Tenant::factory()->create(['id' => 1]);

    expect(fn () => $tenant->delete())
        ->toThrow(Exception::class, 'Tenant ID 1 cannot be deleted as it serves as the primary fallback tenant.');
});

it('allows deletion of other tenants', function () {
    $tenant = Tenant::factory()->create(['id' => 2]);

    expect($tenant->delete())->toBeTrue();
});

it('caches tenant existence permanently once true', function () {
    // Initially no tenants
    expect($this->tenantCache->tenantsExist())->toBeFalse();

    // Create a tenant
    Tenant::factory()->create();

    // Should now return true
    expect($this->tenantCache->tenantsExist())->toBeTrue();

    // Create another tenant to verify cache is still true
    Tenant::factory()->create();
    expect($this->tenantCache->tenantsExist())->toBeTrue();
});

it('caches primary tenant existence permanently once true', function () {
    // Initially no primary tenant
    expect($this->tenantCache->primaryTenantExists())->toBeFalse();

    // Create tenant ID 2 first
    Tenant::factory()->create(['id' => 2]);
    expect($this->tenantCache->primaryTenantExists())->toBeFalse();

    // Create primary tenant
    Tenant::factory()->create(['id' => 1]);
    expect($this->tenantCache->primaryTenantExists())->toBeTrue();
});

it('returns primary tenant from cache', function () {
    $primaryTenant = Tenant::factory()->create([
        'id' => 1,
        'name' => 'Primary App',
        'slug' => 'primary',
    ]);

    $cachedTenant = $this->tenantCache->getPrimaryTenant();

    expect($cachedTenant)->not->toBeNull();
    expect($cachedTenant->id)->toBe(1);
    expect($cachedTenant->name)->toBe('Primary App');
});

it('invalidates existence cache when non-primary tenant is deleted', function () {
    // Create tenants
    Tenant::factory()->create(['id' => 1]);
    $tenant2 = Tenant::factory()->create(['id' => 2]);

    // Cache should show tenants exist
    expect($this->tenantCache->tenantsExist())->toBeTrue();

    // Delete non-primary tenant (this would normally invalidate cache)
    // But since tenant 1 still exists, cache should remain true
    $tenant2->delete();

    expect($this->tenantCache->tenantsExist())->toBeTrue();
});
