<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Roberts\LaravelSingledbTenancy\Events\TenantCreated;
use Roberts\LaravelSingledbTenancy\Events\TenantDeleted;
use Roberts\LaravelSingledbTenancy\Events\TenantReactivated;
use Roberts\LaravelSingledbTenancy\Events\TenantResolved;
use Roberts\LaravelSingledbTenancy\Events\TenantSuspended;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

beforeEach(function () {
    Event::fake([
        TenantCreated::class,
        TenantDeleted::class,
        TenantReactivated::class,
        TenantResolved::class,
        TenantSuspended::class,
    ]);
});

describe('Tenant Lifecycle Events', function () {
    describe('Tenant Creation and Resolution', function () {
        it('dispatches tenant created event', function () {
            $tenant = Tenant::factory()->create();

            Event::assertDispatched(TenantCreated::class, fn ($event) => $event->tenant->id === $tenant->id);
        });

        it('dispatches tenant resolved event', function () {
            $tenant = Tenant::factory()->create(['domain' => 'test.com']);

            // Clear any existing tenant context
            tenant_context()->clear();

            // Manually set up middleware and simulate the request through it
            $middleware = app(\Roberts\LaravelSingledbTenancy\Middleware\TenantResolutionMiddleware::class);
            $request = \Illuminate\Http\Request::create('http://test.com');

            $middleware->handle($request, function () {
                return response('OK');
            });

            Event::assertDispatched(TenantResolved::class, fn ($event) => $event->tenant->id === $tenant->id);
        });
    });

    describe('Tenant Status Changes', function () {
        it('dispatches tenant suspended event', function () {
            $tenant = Tenant::factory()->create(['id' => 999]); // Use non-primary ID
            $tenant->suspend();

            Event::assertDispatched(TenantSuspended::class, fn ($event) => $event->tenant->id === $tenant->id);
        });

        it('dispatches tenant reactivated event', function () {
            $tenant = Tenant::factory()->create(['id' => 998]); // Use non-primary ID
            $tenant->suspend();
            $tenant->reactivate();

            Event::assertDispatched(TenantReactivated::class, fn ($event) => $event->tenant->id === $tenant->id);
        });

        it('dispatches tenant deleted event', function () {
            $tenant = Tenant::factory()->create(['id' => 997]); // Use non-primary ID
            $tenant->forceDelete();

            Event::assertDispatched(TenantDeleted::class, fn ($event) => $event->tenant->id === $tenant->id);
        });
    });
});
