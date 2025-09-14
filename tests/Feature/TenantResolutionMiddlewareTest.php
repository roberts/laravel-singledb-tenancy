<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Middleware\TenantResolutionMiddleware;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\SmartFallback;

beforeEach(function () {
    tenant_context()->clear();
    $this->middleware = app(TenantResolutionMiddleware::class);
    $this->handler = fn ($req) => response('OK');
});

describe('TenantResolutionMiddleware', function () {
    describe('Domain Resolution', function () {
        it('resolves tenant by domain', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);
            $request = Request::create('https://example.test/dashboard');

            $response = $this->middleware->handle($request, $this->handler);

            expect($response->getStatusCode())->toBe(200)
                ->and(current_tenant())->not->toBeNull()
                ->and(current_tenant()->id)->toBe($tenant->id);
        });

        it('resolves tenant by subdomain using domain field', function () {
            $tenant = Tenant::factory()->create(['domain' => 'acme.app.test']);
            $request = Request::create('https://acme.app.test/dashboard');

            $response = $this->middleware->handle($request, $this->handler);

            expect($response->getStatusCode())->toBe(200)
                ->and(current_tenant())->not->toBeNull()
                ->and(current_tenant()->id)->toBe($tenant->id);
        });

        it('does not resolve suspended tenants', function () {
            $tenant = Tenant::factory()->create(['id' => 2, 'domain' => 'example.test']);
            $tenant->suspend();
            $request = Request::create('https://example.test/dashboard');

            $response = $this->middleware->handle($request, $this->handler);

            expect($response->getStatusCode())->toBe(200)
                ->and(current_tenant())->toBeNull();
        });
    });

    describe('Failure Handling', function () {
        it('continues without tenant when none resolved and handling is continue', function () {
            config(['singledb-tenancy.failure_handling.unresolved_tenant' => 'continue']);
            $request = Request::create('https://nonexistent.test/dashboard');

            $response = $this->middleware->handle($request, $this->handler);

            expect($response->getStatusCode())->toBe(200)
                ->and(current_tenant())->toBeNull();
        });

        it('throws exception when no tenant resolved and handling is exception', function () {
            // Create tenant to disable smart fallback but avoid ID 1
            Tenant::factory()->create(['id' => 2, 'domain' => 'some-other-domain.com']);
            app(SmartFallback::class)->permanentlyCacheTenantsExist();
            
            config(['singledb-tenancy.failure_handling.unresolved_tenant' => 'exception']);
            $request = Request::create('http://non-existent.example.com');

            expect(fn () => $this->middleware->handle($request, $this->handler))
                ->toThrow(RuntimeException::class, 'Could not resolve tenant from request');
        });
    });

    describe('Development Features', function () {
        it('uses forced tenant in development', function () {
            $tenant = Tenant::factory()->create(['domain' => 'test-tenant.example.com']);
            config(['singledb-tenancy.development.force_tenant' => 'test-tenant.example.com']);
            $request = Request::create('https://any-domain.test/dashboard');

            $response = $this->middleware->handle($request, $this->handler);

            expect(current_tenant())->not->toBeNull()
                ->and(current_tenant()->id)->toBe($tenant->id);
        });
    });
});
