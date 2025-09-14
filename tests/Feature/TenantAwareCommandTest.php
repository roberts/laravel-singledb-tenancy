<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Roberts\LaravelSingledbTenancy\Commands\TenantAwareCommand;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

beforeEach(function () {
    config(['singledb-tenancy.tenant_model' => Tenant::class]);
});

describe('TenantAware Commands', function () {
    describe('Command Execution', function () {
        it('runs for a specific tenant', function () {
            [$tenant1, $tenant2] = Tenant::factory()->count(2)->create();
            $this->app->bind('command.test', TestCommand::class);
            $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand(app('command.test'));

            $this->artisan('test:command', ['--tenant' => $tenant1->id])
                ->expectsOutput("Running for tenant #{$tenant1->id} ({$tenant1->name})...")
                ->expectsOutput("Handled for tenant: {$tenant1->id}")
                ->doesntExpectOutput("Handled for tenant: {$tenant2->id}")
                ->assertExitCode(0);
        });

        it('runs for all tenants', function () {
            [$tenant1, $tenant2] = Tenant::factory()->count(2)->create();
            $this->app->bind('command.test', TestCommand::class);
            $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand(app('command.test'));

            $this->artisan('test:command', ['--all-tenants' => true])
                ->expectsOutput("Running for tenant #{$tenant1->id} ({$tenant1->name})...")
                ->expectsOutput("Handled for tenant: {$tenant1->id}")
                ->expectsOutput("Running for tenant #{$tenant2->id} ({$tenant2->name})...")
                ->expectsOutput("Handled for tenant: {$tenant2->id}")
                ->assertExitCode(0);
        });

        it('sets the tenant context', function () {
            $tenant = Tenant::factory()->create();
            $this->app->bind('command.test-context-check', TestCommandWithContextCheck::class);
            $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand(app('command.test-context-check'));

            $this->artisan('test:context-command', ['--tenant' => $tenant->id])
                ->expectsOutput("Context tenant ID: {$tenant->id}")
                ->assertExitCode(0);
        });
    });
});

class TestCommand extends TenantAwareCommand
{
    protected $signature = 'test:command';

    protected function handleTenant(): void
    {
        $this->info('Handled for tenant: '.current_tenant_id());
    }
}

class TestCommandWithContextCheck extends TenantAwareCommand
{
    protected $signature = 'test:context-command';

    protected function handleTenant(): void
    {
        $this->info('Context tenant ID: '.current_tenant_id());
    }
}
