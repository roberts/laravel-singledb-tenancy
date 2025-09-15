<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\TenantResource;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

describe('Filament Tenant Resource Authorization', function () {
    it('allows access to tenant management for primary tenant', function () {
        $primaryTenant = Tenant::factory()->create(['id' => 1, 'slug' => 'primary']);
        tenant_context()->set($primaryTenant);

        // Create a test route using the same middleware as TenantResource
        Route::get('/admin/tenants', fn () => 'Tenant Management')
            ->middleware(TenantResource::getMiddleware());

        $this->get('/admin/tenants')->assertOk();
    });

    it('denies access to tenant management for non-primary tenant', function () {
        $nonPrimaryTenant = Tenant::factory()->create(['id' => 2, 'slug' => 'other']);
        tenant_context()->set($nonPrimaryTenant);

        Route::get('/admin/tenants', fn () => 'Tenant Management')
            ->middleware(TenantResource::getMiddleware());

        $this->get('/admin/tenants')->assertNotFound();
    });

    it('denies access when no tenant is set but tenants exist', function () {
        // Create tenants to ensure we're not in fallback mode
        Tenant::factory()->create(['id' => 1, 'slug' => 'primary']);
        Tenant::factory()->create(['id' => 2, 'slug' => 'other']);
        tenant_context()->clear();

        Route::get('/admin/tenants', fn () => 'Tenant Management')
            ->middleware(TenantResource::getMiddleware());

        $this->get('/admin/tenants')->assertNotFound();
    });

    it('allows access when no tenants exist in database (fallback mode)', function () {
        // Ensure no tenants exist - fallback mode should allow access
        Tenant::query()->forceDelete();
        expect(Tenant::count())->toBe(0);

        tenant_context()->clear();

        Route::get('/admin/tenants', fn () => 'Tenant Management')
            ->middleware(TenantResource::getMiddleware());

        $this->get('/admin/tenants')->assertOk();
    });

    it('primary tenant can access tenant creation', function () {
        $primaryTenant = Tenant::factory()->create(['id' => 1, 'slug' => 'primary']);
        tenant_context()->set($primaryTenant);

        Route::post('/_test/create-tenant', fn () => 'Create Tenant')
            ->middleware(TenantResource::getMiddleware());

        $this->post('/_test/create-tenant')->assertOk();
    });

    it('non-primary tenant cannot access tenant creation', function () {
        $nonPrimaryTenant = Tenant::factory()->create(['id' => 2, 'slug' => 'other']);
        tenant_context()->set($nonPrimaryTenant);

        Route::post('/_test/create-tenant', fn () => 'Create Tenant')
            ->middleware(TenantResource::getMiddleware());

        $this->post('/_test/create-tenant')->assertNotFound();
    });

    it('primary tenant can edit any tenant including itself', function () {
        $primaryTenant = Tenant::factory()->create(['id' => 1, 'slug' => 'primary']);
        $otherTenant = Tenant::factory()->create(['id' => 2, 'slug' => 'other']);
        tenant_context()->set($primaryTenant);

        Route::put('/_test/edit-tenant/{tenant}', fn (Tenant $tenant) => "Editing: {$tenant->slug}")
            ->middleware(TenantResource::getMiddleware());

        // Can edit itself
        $this->put("/_test/edit-tenant/{$primaryTenant->slug}")->assertOk();

        // Can edit other tenants
        $this->put("/_test/edit-tenant/{$otherTenant->slug}")->assertOk();
    });

    it('non-primary tenant cannot edit any tenant including itself', function () {
        Tenant::factory()->create(['id' => 1, 'slug' => 'primary']);
        $nonPrimaryTenant = Tenant::factory()->create(['id' => 2, 'slug' => 'other']);
        $anotherTenant = Tenant::factory()->create(['id' => 3, 'slug' => 'another']);
        tenant_context()->set($nonPrimaryTenant);

        Route::put('/_test/edit-tenant/{tenant}', fn (Tenant $tenant) => "Editing: {$tenant->slug}")
            ->middleware(TenantResource::getMiddleware());

        // Cannot edit itself
        $this->put("/_test/edit-tenant/{$nonPrimaryTenant->slug}")->assertNotFound();

        // Cannot edit other tenants
        $this->put("/_test/edit-tenant/{$anotherTenant->slug}")->assertNotFound();
    });
});
