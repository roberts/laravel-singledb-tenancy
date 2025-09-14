<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Commands;

use Illuminate\Console\Command;
use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

abstract class TenantAwareCommand extends Command
{
    public function __construct()
    {
        parent::__construct();

        $this->addOption('tenant', null, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'The tenant ID to run the command for.');
        $this->addOption('all-tenants', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Run the command for all tenants.');
    }

    public function handle()
    {
        if ($this->option('all-tenants')) {
            Tenant::query()->cursor()->each(
                fn (Tenant $tenant) => $this->runForTenant($tenant)
            );

            return 0;
        }

        if ($tenantId = $this->option('tenant')) {
            if (! $tenant = Tenant::find($tenantId)) {
                $this->error("Tenant with ID {$tenantId} not found.");

                return 1;
            }

            $this->runForTenant($tenant);

            return 0;
        }

        $this->handleTenant();

        return 0;
    }

    protected function runForTenant(Tenant $tenant): void
    {
        $this->line("Running for tenant #{$tenant->id} ({$tenant->name})...");

        app(TenantContext::class)->runWith($tenant, fn () => $this->handleTenant());
    }

    abstract protected function handleTenant(): void;
}
