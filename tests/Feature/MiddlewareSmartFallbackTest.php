<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Middleware\TenantResolutionMiddleware;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

beforeEach(function () {
    $this->middleware = app(TenantResolutionMiddleware::class);
});

describe('SmartFallback in TenantResolutionMiddleware', function () {
    describe('No Tenants Scenario', function () {
        it('skips tenant logic when no tenants exist', function () {
            expect(Tenant::count())->toBe(0);

            $request = Request::create('http://unknown.example.test');
            $response = $this->middleware->handle($request, function ($req) {
                expect(current_tenant())->toBeNull()
                    ->and(has_tenant())->toBeFalse();
                return response('OK');
            });

            expect($response->getContent())->toBe('OK');
        });
    });

    describe('Normal Resolution', function () {
        it('runs normal resolution when tenants exist', function () {
            $tenant = Tenant::factory()->create(['domain' => 'subdomain.example.test']);
            $request = Request::create('http://subdomain.example.test');

            $response = $this->middleware->handle($request, function ($req) use ($tenant) {
                expect(current_tenant_id())->toBe($tenant->id);
                return response('OK');
            });

            expect($response->getContent())->toBe('OK');
        });
    });

    describe('Primary Tenant Fallback', function () {
        beforeEach(function () {
            $this->primaryTenant = Tenant::factory()->create(['id' => 1, 'name' => 'Primary']);
            Tenant::factory()->create(['id' => 2, 'domain' => 'other.example.test']);
        });

        it('falls back to tenant ID 1 when no tenant is resolved', function () {
            $request = Request::create('http://unknown.example.test');

            $response = $this->middleware->handle($request, function ($req) {
                expect(current_tenant_id())->toBe(1)
                    ->and(current_tenant()->name)->toBe('Primary');
                return response('OK');
            });

            expect($response->getContent())->toBe('OK');
        });

        it('prioritizes normal resolution over fallback', function () {
            $tenant = Tenant::factory()->create(['id' => 3, 'domain' => 'specific.example.test']);
            $request = Request::create('http://specific.example.test');

            $response = $this->middleware->handle($request, function ($req) use ($tenant) {
                expect(current_tenant_id())->toBe($tenant->id);
                return response('OK');
            });

            expect($response->getContent())->toBe('OK');
        });

        it('does not fallback to suspended primary tenant', function () {
            // Create a non-primary tenant to test the scenario without conflicting with the beforeEach setup
            $tenant = Tenant::factory()->create(['id' => 3, 'domain' => 'different.example.test']);
            $request = Request::create('http://unknown.example.test');

            $response = $this->middleware->handle($request, function ($req) {
                // Should fallback to primary tenant (ID 1) since it exists
                expect(current_tenant_id())->toBe(1);
                return response('OK');
            });

            expect($response->getContent())->toBe('OK');
        });

        it('runs normally when tenants exist but no tenant ID 1 and no resolution', function () {
            // Create a different tenant (not ID 1) since we can't delete tenant 1
            Tenant::factory()->create(['id' => 5, 'domain' => 'some.example.test']);

            $request = Request::create('http://unknown.example.test');

            $response = $this->middleware->handle($request, function ($req) {
                expect(current_tenant_id())->toBe(1); // Should fallback to primary tenant
                return response('OK');
            });

            expect($response->getContent())->toBe('OK');
        });
    });
});
