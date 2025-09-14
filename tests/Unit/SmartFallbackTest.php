<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\SmartFallback;

beforeEach(function () {
    $this->smartFallback = app(SmartFallback::class);

    // Clear cache before each test
    Cache::forget(SmartFallback::CACHE_KEY);
});

describe('SmartFallback Service', function () {
    it('returns true when no tenants exist', function () {
        // Ensure no tenants exist
        Tenant::query()->delete();

        expect($this->smartFallback->isFallback())->toBeTrue();
    });

    it('returns false when tenants exist', function () {
        // Create a tenant
        Tenant::factory()->create();

        expect($this->smartFallback->isFallback())->toBeFalse();
    });

    it('caches the result when tenants are found', function () {
        // Create a tenant
        Tenant::factory()->create();

        // First call should cache the result
        expect($this->smartFallback->isFallback())->toBeFalse();
        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();

        // Delete all tenants
        Tenant::query()->delete();

        // Should still return false due to caching
        expect($this->smartFallback->isFallback())->toBeFalse();
    });

    it('does not cache when no tenants exist', function () {
        // Ensure no tenants exist
        Tenant::query()->delete();

        expect($this->smartFallback->isFallback())->toBeTrue();
        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeNull();
    });

    it('uses cached value when available', function () {
        // Pre-set the cache
        Cache::forever(SmartFallback::CACHE_KEY, true);

        // Should return false immediately without checking database
        expect($this->smartFallback->isFallback())->toBeFalse();
    });

    it('permanently caches tenant existence', function () {
        $this->smartFallback->permanentlyCacheTenantsExist();

        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
        expect($this->smartFallback->isFallback())->toBeFalse();
    });

    it('handles missing tenants table gracefully', function () {
        // Mock schema to return false for table existence
        Schema::shouldReceive('hasTable')
            ->with('tenants')
            ->once()
            ->andReturn(false);

        expect($this->smartFallback->isFallback())->toBeTrue();
    });

    it('checks database when cache is not set', function () {
        // Create a tenant
        $tenant = Tenant::factory()->create();

        // Clear cache
        Cache::forget(SmartFallback::CACHE_KEY);

        // Should check database and find tenant
        expect($this->smartFallback->isFallback())->toBeFalse();

        // Should now have cached the result
        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
    });

    it('optimizes subsequent calls with cache', function () {
        // Set cache manually
        Cache::forever(SmartFallback::CACHE_KEY, true);

        // Multiple calls should use cache
        expect($this->smartFallback->isFallback())->toBeFalse();
        expect($this->smartFallback->isFallback())->toBeFalse();
        expect($this->smartFallback->isFallback())->toBeFalse();

        // Cache should still be set
        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
    });

    it('handles soft deleted tenants correctly', function () {
        // Create multiple tenants to avoid issues with tenant ID 1
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Delete the non-primary tenant
        $tenantToDelete = $tenant1->id !== 1 ? $tenant1 : $tenant2;
        $tenantToDelete->delete(); // Soft delete

        // Should still return false since at least one active tenant exists
        expect($this->smartFallback->isFallback())->toBeFalse();
    });

    it('detects active tenants correctly', function () {
        // Create active tenant
        $activeTenant = Tenant::factory()->create();

        // Create and soft delete another tenant
        $deletedTenant = Tenant::factory()->create();
        $deletedTenant->delete();

        // Should detect the active tenant
        expect($this->smartFallback->isFallback())->toBeFalse();
        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
    });
});
