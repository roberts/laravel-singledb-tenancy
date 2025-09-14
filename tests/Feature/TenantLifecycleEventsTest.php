<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Roberts\LaravelSingledbTenancy\Events\TenantCreated;
use Roberts\LaravelSingledbTenancy\Events\TenantDeleted;
use Roberts\LaravelSingledbTenancy\Events\TenantReactivated;
use Roberts\LaravelSingledbTenancy\Events\TenantResolved;
use Roberts\LaravelSingledbTenancy\Events\TenantSuspended;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Tests\TestCase;

class TenantLifecycleEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    /** @test */
    public function it_dispatches_tenant_created_event()
    {
        $tenant = Tenant::factory()->create();

        Event::assertDispatched(TenantCreated::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    /** @test */
    public function it_dispatches_tenant_resolved_event()
    {
        $tenant = Tenant::factory()->create(['domain' => 'test.com']);

        $this->get('http://test.com');

        Event::assertDispatched(TenantResolved::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    /** @test */
    public function it_dispatches_tenant_suspended_event()
    {
        $tenant = Tenant::factory()->create();

        $tenant->suspend();

        Event::assertDispatched(TenantSuspended::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    /** @test */
    public function it_dispatches_tenant_reactivated_event()
    {
        $tenant = Tenant::factory()->create();
        $tenant->suspend();

        $tenant->reactivate();

        Event::assertDispatched(TenantReactivated::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    /** @test */
    public function it_dispatches_tenant_deleted_event()
    {
        $tenant = Tenant::factory()->create();

        $tenant->forceDelete();

        Event::assertDispatched(TenantDeleted::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }
}
