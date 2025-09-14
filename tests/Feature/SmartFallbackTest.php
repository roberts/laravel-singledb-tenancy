<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Roberts\LaravelSingledbTenancy\Events\TenantCreated;
use Roberts\LaravelSingledbTenancy\Listeners\CacheTenantsExist;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\SmartFallback;

beforeEach(function () {
    $this->smartFallback = app(SmartFallback::class);
    Cache::forget(SmartFallback::CACHE_KEY);
});

describe('SmartFallback Service', function () {
    it('is in fallback mode when no tenants exist', function () {
        expect(Tenant::count())->toBe(0);
        expect($this->smartFallback->isFallback())->toBeTrue();
    });

    it('is not in fallback mode when tenants exist', function () {
        Tenant::factory()->create();
        expect($this->smartFallback->isFallback())->toBeFalse();
    });

    it('permanently caches that tenants exist', function () {
        // First call, no tenants exist.
        expect($this->smartFallback->isFallback())->toBeTrue();

        // Create a tenant. The next call should cache the result.
        Tenant::factory()->create();
        expect($this->smartFallback->isFallback())->toBeFalse();
        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();

        // Delete the tenant. The cache should still report that tenants exist.
        Tenant::query()->delete();
        expect(Tenant::count())->toBe(0);
        expect($this->smartFallback->isFallback())->toBeFalse('Should use the permanent cache value');
    });

    it('tenant created event triggers the caching', function () {
        Event::fake();

        $tenant = Tenant::factory()->make();
        
        // Manually fire the event since Event::fake() prevents model events
        event(new TenantCreated($tenant));

        Event::assertDispatched(TenantCreated::class, function ($event) use ($tenant) {
            return $event->tenant->name === $tenant->name;
        });

        // Manually trigger the listener
        $listener = app(CacheTenantsExist::class);
        $listener->handle();

        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
    });

    it('command caches the status', function () {
        $this->artisan('tenancy:cache-fallback-status')
            ->expectsOutput('No tenants found. Fallback mode is active.')
            ->assertSuccessful();

        expect(Cache::has(SmartFallback::CACHE_KEY))->toBeFalse();

        Tenant::factory()->create();

        $this->artisan('tenancy:cache-fallback-status')
            ->expectsOutput('Tenants found. Fallback mode is disabled and this status is now permanently cached.')
            ->assertSuccessful();

        expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
    });

    it('is in fallback mode if tenants table does not exist', function () {
        Schema::drop('tenants');
        expect($this->smartFallback->isFallback())->toBeTrue();
    });
});
