<?php

namespace Roberts\LaravelSingledbTenancy\Commands;

use Illuminate\Console\Command;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class TenancyInfoCommand extends Command
{
    public $signature = 'tenancy:info';

    public $description = 'Display information about the current tenancy configuration';

    public function handle(): int
    {
        $this->info('Laravel Single Database Tenancy - Configuration Info');
        $this->line('=================================================');
        $this->newLine();

        // Basic configuration
        $tenantModel = config('singledb-tenancy.tenant_model');
        $tenantColumn = config('singledb-tenancy.tenant_column');

        $tenantModelStr = is_string($tenantModel) ? $tenantModel : 'Unknown';
        $tenantColumnStr = is_string($tenantColumn) ? $tenantColumn : 'tenant_id';

        $this->info('Configuration:');
        $this->line("  Tenant Model: {$tenantModelStr}");
        $this->line("  Tenant Column: {$tenantColumnStr}");
        $this->newLine();

        // Caching
        $cacheEnabled = config('singledb-tenancy.caching.enabled', false);
        $cacheStore = config('singledb-tenancy.caching.store', 'default');
        $cacheStoreStr = is_string($cacheStore) ? $cacheStore : 'default';
        $this->info('Caching:');
        $this->line('  Enabled: '.($cacheEnabled ? 'Yes' : 'No'));
        $this->line("  Store: {$cacheStoreStr}");
        $this->newLine();

        // Tenant statistics
        try {
            if (is_string($tenantModel) && class_exists($tenantModel)) {
                $totalTenants = $tenantModel::withTrashed()->count();
                $activeTenants = $tenantModel::whereNull('deleted_at')->count();
                $suspendedTenants = $totalTenants - $activeTenants;

                $this->info('Tenant Statistics:');
                $this->line("  Total Tenants: {$totalTenants}");
                $this->line("  Active Tenants: {$activeTenants}");
                $this->line("  Suspended Tenants: {$suspendedTenants}");
            } else {
                $this->warn('Could not determine tenant model class.');
            }
        } catch (\Exception $e) {
            $this->warn('Could not fetch tenant statistics. Make sure migrations are run.');
        }

        $this->newLine();

        // Current context using helper function
        if (has_tenant()) {
            $tenant = current_tenant();
            if ($tenant) {
                $this->info('Current Tenant Context:');
                $this->line("  ID: {$tenant->id}");
                $this->line("  Name: {$tenant->name}");
                $this->line("  Slug: {$tenant->slug}");
                if ($tenant->domain) {
                    $this->line("  Domain: {$tenant->domain}");
                }
            } else {
                $this->comment('No tenant context currently set.');
            }
        } else {
            $this->comment('No tenant context currently set.');
        }

        return self::SUCCESS;
    }
}
