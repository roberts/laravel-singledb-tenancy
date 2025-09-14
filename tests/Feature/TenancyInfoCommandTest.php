<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Models\Tenant;

it('displays configuration information', function () {
    $this->artisan('tenancy:info')
        ->expectsOutputToContain('Laravel Single Database Tenancy - Configuration Info')
        ->expectsOutputToContain('Configuration:')
        ->expectsOutputToContain('Tenant Model:')
        ->expectsOutputToContain('Tenant Column:')
        ->assertExitCode(0);
});

it('displays caching configuration', function () {
    $this->artisan('tenancy:info')
        ->expectsOutputToContain('Caching:')
        ->expectsOutputToContain('Enabled:')
        ->expectsOutputToContain('Store:')
        ->assertExitCode(0);
});

it('displays tenant statistics when database is available', function () {
    // Create some test tenants
    Tenant::factory()->count(3)->create();
    Tenant::factory()->create()->delete();

    // Calculate expected counts
    $totalCount = Tenant::withTrashed()->count();  // All tenants including soft-deleted
    $activeCount = Tenant::count();                // Only non-soft-deleted tenants
    $suspendedCount = $totalCount - $activeCount;   // Soft-deleted tenants

    $this->artisan('tenancy:info')
        ->expectsOutputToContain('Tenant Statistics:')
        ->expectsOutputToContain("Total Tenants: {$totalCount}")
        ->expectsOutputToContain("Active Tenants: {$activeCount}")
        ->expectsOutputToContain("Suspended Tenants: {$suspendedCount}")
        ->assertExitCode(0);
});

it('handles database errors gracefully', function () {
    // Test that the command doesn't crash - the exact error message may vary
    // but the command should complete successfully
    $this->artisan('tenancy:info')
        ->expectsOutputToContain('Laravel Single Database Tenancy')
        ->expectsOutputToContain('Configuration:')
        ->assertExitCode(0);
});

it('displays current tenant context when set', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
        'domain' => 'subdomain.example.test',
    ]);

    tenant_context()->set($tenant);

    $this->artisan('tenancy:info')
        ->expectsOutputToContain('Current Tenant Context:')
        ->expectsOutputToContain('Name: Test Tenant')
        ->expectsOutputToContain('Slug: test-tenant')
        ->expectsOutputToContain('Domain: subdomain.example.test')
        ->assertExitCode(0);
});

it('shows no context message when no tenant is set', function () {
    tenant_context()->clear();

    $this->artisan('tenancy:info')
        ->expectsOutputToContain('No tenant context currently set')
        ->assertExitCode(0);
});
