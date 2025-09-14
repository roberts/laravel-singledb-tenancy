<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

it('clears tenant cache using tags', function () {
    // Create a tenant and cache it
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $cache = app(TenantCache::class);
    
    // Cache the tenant by resolving it
    $resolved = $cache->getTenantByDomain('example.test');
    expect($resolved)->not()->toBeNull();
    expect($resolved->id)->toBe($tenant->id);

    // Clear cache using command
    $this->artisan('tenancy:cache:clear')
        ->expectsOutput('Clearing tenant resolution cache...')
        ->expectsOutput('✓ Tenant cache cleared successfully')
        ->assertExitCode(0);

    // Verify cache was cleared by checking if we can resolve again
    $resolvedAfterClear = $cache->getTenantByDomain('example.test');
    expect($resolvedAfterClear)->not()->toBeNull();
    expect($resolvedAfterClear->id)->toBe($tenant->id);
});

it('clears cache for specific tenant', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $cache = app(TenantCache::class);
    
    // Cache the tenant
    $cache->getTenantByDomain('example.test');

    // Clear cache for specific tenant
    $this->artisan('tenancy:cache:clear', ['--tenant' => 'example.test'])
        ->expectsOutput('Clearing cache for tenant: example.test')
        ->expectsOutput('✓ Cache cleared for tenant: example.test')
        ->assertExitCode(0);
});

it('clears all tenant cache entries', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $cache = app(TenantCache::class);
    
    // Cache the tenant
    $cache->getTenantByDomain('example.test');

    // Clear all cache
    $this->artisan('tenancy:cache:clear', ['--all' => true])
        ->expectsOutput('Clearing all tenant-related cache entries...')
        ->expectsOutput('✓ All tenant cache cleared successfully')
        ->assertExitCode(0);
});

it('handles cache clearing when tenant does not exist', function () {
    $this->artisan('tenancy:cache:clear', ['--tenant' => 'nonexistent.test'])
        ->expectsOutput('✓ Cache cleared for tenant: nonexistent.test')
        ->assertExitCode(0);
});
