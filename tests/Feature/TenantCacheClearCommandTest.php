<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

beforeEach(function () {
    $this->cache = app(TenantCache::class);
});

describe('TenantCacheClearCommand', function () {
    describe('Cache Operations', function () {
        it('clears tenant cache using tags', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);
            
            $resolved = $this->cache->getTenantByDomain('example.test');
            expect($resolved)->not()->toBeNull()->and($resolved->id)->toBe($tenant->id);

            $this->artisan('tenancy:cache:clear')
                ->expectsOutput('Clearing tenant resolution cache...')
                ->expectsOutput('✓ Tenant cache cleared successfully')
                ->assertExitCode(0);

            $resolvedAfterClear = $this->cache->getTenantByDomain('example.test');
            expect($resolvedAfterClear)->not()->toBeNull()->and($resolvedAfterClear->id)->toBe($tenant->id);
        });

        it('clears cache for specific tenant', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);
            $this->cache->getTenantByDomain('example.test');

            $this->artisan('tenancy:cache:clear', ['--tenant' => 'example.test'])
                ->expectsOutput('Clearing cache for tenant: example.test')
                ->expectsOutput('✓ Cache cleared for tenant: example.test')
                ->assertExitCode(0);
        });

        it('clears all tenant cache entries', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);
            $this->cache->getTenantByDomain('example.test');

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
    });
});
