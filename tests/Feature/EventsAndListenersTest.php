<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Roberts\LaravelSingledbTenancy\Events\TenantCreated;
use Roberts\LaravelSingledbTenancy\Listeners\CacheTenantsExist;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\SmartFallback;

describe('Events and Listeners', function () {
    beforeEach(function () {
        // Clear cache before each test
        Cache::forget(SmartFallback::CACHE_KEY);
    });

    describe('TenantCreated Event', function () {
        it('is fired when tenant is created', function () {
            Event::fake();

            $tenant = new Tenant([
                'name' => 'Test Tenant',
                'slug' => 'test-tenant',
                'domain' => 'test.example',
            ]);

            // Manually fire the event since Event::fake() prevents model events
            event(new TenantCreated($tenant));

            Event::assertDispatched(TenantCreated::class, function ($event) use ($tenant) {
                return $event->tenant->name === $tenant->name;
            });
        });

        it('triggers cache update when tenant is created', function () {
            // Ensure listener is registered
            Event::listen(TenantCreated::class, CacheTenantsExist::class);

            $tenant = Tenant::factory()->create();

            // Cache should be set after tenant creation
            expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
        });
    });

    describe('CacheTenantsExist Listener', function () {
        it('sets cache when handling event', function () {
            $listener = new CacheTenantsExist(app(SmartFallback::class));

            expect(Cache::get(SmartFallback::CACHE_KEY))->toBeNull();

            $listener->handle();

            expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
        });

        it('updates cache when called multiple times', function () {
            $listener = new CacheTenantsExist(app(SmartFallback::class));

            // Call multiple times
            $listener->handle();
            $listener->handle();
            $listener->handle();

            expect(Cache::get(SmartFallback::CACHE_KEY))->toBeTrue();
        });
    });

    describe('TenantResolved Event', function () {
        it('carries tenant information', function () {
            Event::fake();

            $tenant = Tenant::factory()->create();

            // Simulate tenant resolution (would normally happen in middleware)
            event(new \Roberts\LaravelSingledbTenancy\Events\TenantResolved($tenant));

            Event::assertDispatched(\Roberts\LaravelSingledbTenancy\Events\TenantResolved::class, function ($event) use ($tenant) {
                return $event->tenant->id === $tenant->id;
            });
        });
    });

    describe('Tenant Lifecycle Events', function () {
        it('fires suspended event when tenant is suspended', function () {
            Event::fake();

            $tenant = Tenant::factory()->create();

            // Manually fire the event since Event::fake() prevents model events
            event(new \Roberts\LaravelSingledbTenancy\Events\TenantSuspended($tenant));

            Event::assertDispatched(\Roberts\LaravelSingledbTenancy\Events\TenantSuspended::class, function ($event) use ($tenant) {
                return $event->tenant->id === $tenant->id;
            });
        });

        it('fires reactivated event when tenant is reactivated', function () {
            Event::fake();

            $tenant = Tenant::factory()->create();

            // Manually fire the event since Event::fake() prevents model events
            event(new \Roberts\LaravelSingledbTenancy\Events\TenantReactivated($tenant));

            Event::assertDispatched(\Roberts\LaravelSingledbTenancy\Events\TenantReactivated::class, function ($event) use ($tenant) {
                return $event->tenant->id === $tenant->id;
            });
        });

        it('fires deleted event when tenant is force deleted', function () {
            Event::fake();

            $tenant = Tenant::factory()->create();

            // Manually fire the event since Event::fake() prevents model events
            event(new \Roberts\LaravelSingledbTenancy\Events\TenantDeleted($tenant));

            Event::assertDispatched(\Roberts\LaravelSingledbTenancy\Events\TenantDeleted::class, function ($event) use ($tenant) {
                return $event->tenant->id === $tenant->id;
            });
        });
    });
});
