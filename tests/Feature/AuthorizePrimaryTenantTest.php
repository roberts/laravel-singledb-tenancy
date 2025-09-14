<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Roberts\LaravelSingledbTenancy\Middleware\AuthorizePrimaryTenant;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

beforeEach(function () {
    Route::get('/_test/protected-route', fn () => 'OK')
        ->middleware(AuthorizePrimaryTenant::class);
});

describe('AuthorizePrimaryTenant Middleware', function () {
    it('allows access when the current tenant is the primary tenant', function () {
        $primaryTenant = Tenant::factory()->create(['id' => 1]);
        tenant_context()->set($primaryTenant);

        $this->get('/_test/protected-route')->assertOk();
    });

    it('aborts with 404 when the current tenant is not the primary tenant', function () {
        $otherTenant = Tenant::factory()->create(['id' => 2]);
        tenant_context()->set($otherTenant);

        $this->get('/_test/protected-route')->assertNotFound();
    });

    it('aborts with 404 when no tenant is set', function () {
        // Create a tenant so we're not in fallback mode
        Tenant::factory()->create(['id' => 2]);

        tenant_context()->clear();

        $this->get('/_test/protected-route')->assertNotFound();
    });

    it('allows access when no tenants exist in the database', function () {
        expect(Tenant::count())->toBe(0);

        tenant_context()->clear();

        $this->get('/_test/protected-route')->assertOk();
    });
});
