<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Middleware\TenantResolutionMiddleware;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

it('skips tenant logic when no tenants exist', function () {
    // Ensure no tenants exist
    expect(Tenant::count())->toBe(0);

    $request = Request::create('http://unknown.example.test');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        // Verify no tenant context is set
        expect(current_tenant())->toBeNull();
        expect(has_tenant())->toBeFalse();

        return response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('runs normal resolution when tenants exist', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create([
        'domain' => 'subdomain.example.test',
    ]);

    $request = Request::create('http://subdomain.example.test');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) use ($tenant) {
        // Verify correct tenant context is set
        expect(current_tenant_id())->toBe($tenant->id);

        return response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('falls back to tenant ID 1 when no tenant is resolved', function () {
    // Create primary tenant and another tenant
    $primaryTenant = Tenant::factory()->create(['id' => 1, 'name' => 'Primary']);
    Tenant::factory()->create(['id' => 2, 'domain' => 'other.example.test']);

    // Request to unknown domain
    $request = Request::create('http://unknown.example.test');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        // Verify fallback to primary tenant
        expect(current_tenant_id())->toBe(1);
        expect(current_tenant()->name)->toBe('Primary');

        return response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('runs normally when tenants exist but no tenant ID 1 and no resolution', function () {
    // Create tenant with ID 2 (no primary tenant)
    Tenant::factory()->create(['id' => 2, 'domain' => 'other.example.test']);

    // Request to unknown domain
    $request = Request::create('http://unknown.example.test');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        // No tenant context should be set (follows existing unresolved logic)
        expect(current_tenant())->toBeNull();

        return response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('prioritizes normal resolution over fallback', function () {
    // Create primary tenant and specific domain tenant
    Tenant::factory()->create(['id' => 1, 'name' => 'Primary']);
    $domainTenant = Tenant::factory()->create(['id' => 2, 'domain' => 'subdomain.example.test']);

    // Request to specific domain should resolve to domain tenant, not fallback
    $request = Request::create('http://subdomain.example.test');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) use ($domainTenant) {
        // Should resolve to domain tenant, not primary
        expect(current_tenant_id())->toBe($domainTenant->id);

        return response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('does not fallback to suspended primary tenant', function () {
    // Create tenant ID 1 and suspend it
    $tenant = Tenant::factory()->create(['id' => 1, 'domain' => 'tenant1.example.test']);

    // Suspend the tenant directly by updating the deleted_at field
    $tenant->deleted_at = now();
    $tenant->save();

    // Fresh instance from database to ensure state is correct
    $freshTenant = Tenant::withTrashed()->find(1);

    // Verify tenant is suspended
    expect($freshTenant->trashed())->toBeTrue();
    expect($freshTenant->isActive())->toBeFalse();

    // Mock request with unresolved domain
    $request = Request::create('http://nonexistent.example.test/test');

    $middleware = app(TenantResolutionMiddleware::class);
    $middleware->handle($request, function ($req) {
        return response('OK');
    });

    // Should not have fallback tenant set because primary tenant is suspended
    expect(current_tenant())->toBeNull();
});
