<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Middleware\TenantResolutionMiddleware;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

beforeEach(function () {
    // Clear tenant context before each test
    tenant_context()->clear();
});

it('resolves tenant by domain', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $request = Request::create('https://example.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(current_tenant())->not()->toBeNull();
    expect(current_tenant()->id)->toBe($tenant->id);
});

it('resolves tenant by subdomain', function () {
    config(['singledb-tenancy.resolution.subdomain.base_domain' => 'app.test']);

    $tenant = Tenant::factory()->create([
        'slug' => 'acme',
    ]);

    $request = Request::create('https://acme.app.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(current_tenant())->not()->toBeNull();
    expect(current_tenant()->id)->toBe($tenant->id);
});

it('ignores reserved subdomains', function () {
    config([
        'singledb-tenancy.resolution.subdomain.base_domain' => 'app.test',
        'singledb-tenancy.resolution.subdomain.reserved' => ['api', 'admin'],
    ]);

    $request = Request::create('https://api.app.test/v1/users');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(current_tenant())->toBeNull();
});

it('continues without tenant when none resolved and handling is continue', function () {
    config(['singledb-tenancy.failure_handling.unresolved_tenant' => 'continue']);

    $request = Request::create('https://nonexistent.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(current_tenant())->toBeNull();
});

it('throws exception when no tenant resolved and handling is exception', function () {
    config(['singledb-tenancy.failure_handling.unresolved_tenant' => 'exception']);

    // Ensure no tenants exist in database so Smart Fallback Logic won't apply
    // Use withTrashed to clear all including soft deleted ones
    Tenant::withTrashed()->forceDelete();

    // Clear cache to ensure we're not getting cached results
    app(\Roberts\LaravelSingledbTenancy\Services\TenantCache::class)->invalidateExistenceCache();

    // Verify no tenants exist
    expect(Tenant::count())->toBe(0);

    $request = Request::create('https://nonexistent.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    expect(fn () => $middleware->handle($request, function ($req) {
        return response('OK');
    }))->toThrow(RuntimeException::class, 'Could not resolve tenant from request');
});

it('uses specific resolution strategies when provided', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
        'slug' => 'example',
    ]);

    // Test domain-only resolution
    $request = Request::create('https://example.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    }, 'domain');

    expect(current_tenant())->not()->toBeNull();
    expect(current_tenant()->id)->toBe($tenant->id);
});

it('uses forced tenant in development', function () {
    $tenant = Tenant::factory()->create(['slug' => 'test-tenant']);
    config(['singledb-tenancy.development.force_tenant' => 'test-tenant']);

    $request = Request::create('https://any-domain.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect(current_tenant())->not()->toBeNull();
    expect(current_tenant()->id)->toBe($tenant->id);
});

it('does not resolve suspended tenants', function () {
    $tenant = Tenant::factory()->create([
        'id' => 2, // Use ID 2 to avoid deletion protection
        'domain' => 'example.test',
    ]);

    // Suspend the tenant (soft delete)
    $tenant->suspend();

    $request = Request::create('https://example.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    // Since suspended tenants are soft deleted, they won't be found by resolvers
    // So this should continue without tenant (or handle according to unresolved_tenant config)
    expect($response->getStatusCode())->toBe(200);
    expect(current_tenant())->toBeNull();
});

it('prioritizes domain over subdomain resolution', function () {
    config(['singledb-tenancy.resolution.subdomain.base_domain' => 'app.test']);

    $domainTenant = Tenant::factory()->create([
        'domain' => 'acme.app.test',
        'slug' => 'domain-tenant',
    ]);

    $subdomainTenant = Tenant::factory()->create([
        'slug' => 'acme',
    ]);

    $request = Request::create('https://acme.app.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect(current_tenant())->not()->toBeNull();
    expect(current_tenant()->id)->toBe($domainTenant->id); // Domain should win
});

it('falls back to subdomain when domain resolution fails', function () {
    config(['singledb-tenancy.resolution.subdomain.base_domain' => 'app.test']);

    $tenant = Tenant::factory()->create([
        'slug' => 'acme',
    ]);

    // No domain match, should fall back to subdomain
    $request = Request::create('https://acme.app.test/dashboard');
    $middleware = app(TenantResolutionMiddleware::class);

    $response = $middleware->handle($request, function ($req) {
        return response('OK');
    });

    expect(current_tenant())->not()->toBeNull();
    expect(current_tenant()->id)->toBe($tenant->id);
});
