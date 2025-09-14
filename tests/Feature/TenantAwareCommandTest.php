<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Roberts\LaravelSingledbTenancy\Commands\TenantAwareCommand;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Tests\TestCase;

class TenantAwareCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('singledb-tenancy.tenant_model', Tenant::class);
    }

    /** @test */
    public function it_runs_for_a_specific_tenant()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Artisan::register('test:command', TestCommand::class);

        $this->artisan('test:command', ['--tenant' => $tenant1->id])
            ->expectsOutput("Running for tenant #{$tenant1->id} ({$tenant1->name})...")
            ->expectsOutput("Handled for tenant: {$tenant1->id}")
            ->doesntExpectOutput("Handled for tenant: {$tenant2->id}")
            ->assertExitCode(0);
    }

    /** @test */
    public function it_runs_for_all_tenants()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Artisan::register('test:command', TestCommand::class);

        $this->artisan('test:command', ['--all-tenants' => true])
            ->expectsOutput("Running for tenant #{$tenant1->id} ({$tenant1->name})...")
            ->expectsOutput("Handled for tenant: {$tenant1->id}")
            ->expectsOutput("Running for tenant #{$tenant2->id} ({$tenant2->name})...")
            ->expectsOutput("Handled for tenant: {$tenant2->id}")
            ->assertExitCode(0);
    }

    /** @test */
    public function it_sets_the_tenant_context()
    {
        $tenant = Tenant::factory()->create();

        Artisan::register('test:command', TestCommandWithContextCheck::class);

        $this->artisan('test:command', ['--tenant' => $tenant->id])
            ->expectsOutput("Context tenant ID: {$tenant->id}")
            ->assertExitCode(0);
    }
}

class TestCommand extends TenantAwareCommand
{
    protected $signature = 'test:command {--tenant=} {--all-tenants}';

    protected function handleTenant(): void
    {
        $this->info("Handled for tenant: ".current_tenant_id());
    }
}

class TestCommandWithContextCheck extends TenantAwareCommand
{
    protected $signature = 'test:command {--tenant=} {--all-tenants}';

    protected function handleTenant(): void
    {
        $this->info("Context tenant ID: ".current_tenant_id());
    }
}
