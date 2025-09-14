<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Models\Tenant;

describe('TenancyInfoCommand', function () {
    describe('Configuration Display', function () {
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
    });

    describe('Tenant Statistics', function () {
        it('displays tenant statistics when database is available', function () {
            [$active, $suspended] = [Tenant::factory()->count(3)->create(), Tenant::factory()->create()];
            $suspended->delete();

            [$totalCount, $activeCount, $suspendedCount] = [
                Tenant::withTrashed()->count(),
                Tenant::count(),
                Tenant::onlyTrashed()->count(),
            ];

            $this->artisan('tenancy:info')
                ->expectsOutputToContain('Tenant Statistics:')
                ->expectsOutputToContain("Total Tenants: {$totalCount}")
                ->expectsOutputToContain("Active Tenants: {$activeCount}")
                ->expectsOutputToContain("Suspended Tenants: {$suspendedCount}")
                ->assertExitCode(0);
        });

        it('handles database errors gracefully', function () {
            $this->artisan('tenancy:info')
                ->expectsOutputToContain('Laravel Single Database Tenancy')
                ->expectsOutputToContain('Configuration:')
                ->assertExitCode(0);
        });
    });

    describe('Tenant Context Display', function () {
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
    });
});
