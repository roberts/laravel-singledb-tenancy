<?php

namespace Roberts\LaravelSingledbTenancy;

use Filament\Facades\Filament;
use Roberts\LaravelSingledbTenancy\Commands\AddTenantColumnCommand;
use Roberts\LaravelSingledbTenancy\Commands\CacheFallbackStatusCommand;
use Roberts\LaravelSingledbTenancy\Commands\TenancyInfoCommand;
use Roberts\LaravelSingledbTenancy\Commands\TenantAwareCommand;
use Roberts\LaravelSingledbTenancy\Commands\TenantCacheClearCommand;
use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Events\TenantCreated;
use Roberts\LaravelSingledbTenancy\Filament\LaravelSingledbTenancyPlugin;
use Roberts\LaravelSingledbTenancy\Listeners\CacheTenantsExist;
use Roberts\LaravelSingledbTenancy\Middleware\AuthorizePrimaryTenant;
use Roberts\LaravelSingledbTenancy\Middleware\TenantResolutionMiddleware;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Observers\TenantObserver;
use Roberts\LaravelSingledbTenancy\Resolvers\DomainResolver;
use Roberts\LaravelSingledbTenancy\Services\SmartFallback;
use Roberts\LaravelSingledbTenancy\Services\SuperAdmin;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;
use Roberts\LaravelSingledbTenancy\Services\TenantRouteManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSingledbTenancyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-singledb-tenancy')
            ->hasConfigFile()
            ->hasMigration('create_tenants_table')
            ->hasCommands([
                AddTenantColumnCommand::class,
                CacheFallbackStatusCommand::class,
                TenancyInfoCommand::class,
                TenantCacheClearCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // Register the TenantContext as a singleton
        $this->app->singleton(TenantContext::class);

        // Register tenant resolution services
        $this->app->singleton(TenantCache::class);
        $this->app->singleton(DomainResolver::class);
        $this->app->singleton(TenantRouteManager::class);
        $this->app->singleton(SuperAdmin::class);
        $this->app->singleton(SmartFallback::class);

        $this->app->bind('command.tenancy:aware', TenantAwareCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app['router']->aliasMiddleware('auth.primary', AuthorizePrimaryTenant::class);
        $this->app['router']->aliasMiddleware('auth.tenant', TenantResolutionMiddleware::class);

        $this->app['events']->listen(
            TenantCreated::class,
            CacheTenantsExist::class,
        );

        // Register the TenantObserver
        Tenant::observe(TenantObserver::class);

        // Auto-register Filament plugin if Filament is available
        $this->registerFilamentPlugin();
    }

    protected function registerFilamentPlugin(): void
    {
        if (! class_exists('Filament\Facades\Filament')) {
            return;
        }

        // Use booted callback to register plugin with panels after they're configured
        $this->app->booted(function () {
            if (! class_exists('Filament\Facades\Filament')) {
                return;
            }

            try {
                $panels = \Filament\Facades\Filament::getPanels();

                foreach ($panels as $panel) {
                    if (! $panel->hasPlugin('roberts-laravel-singledb-tenancy')) {
                        $panel->plugin(LaravelSingledbTenancyPlugin::make());
                    }
                }
            } catch (\Exception $e) {
                // Silently fail if Filament is not properly configured
                // This can happen during static analysis or testing
            }
        });
    }
}
