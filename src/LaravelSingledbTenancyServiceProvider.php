<?php

namespace Roberts\LaravelSingledbTenancy;

use Illuminate\Routing\Router;
use Roberts\LaravelSingledbTenancy\Commands\AddTenantColumnCommand;
use Roberts\LaravelSingledbTenancy\Commands\CacheFallbackStatusCommand;
use Roberts\LaravelSingledbTenancy\Commands\TenancyInfoCommand;
use Roberts\LaravelSingledbTenancy\Commands\TenantAwareCommand;
use Roberts\LaravelSingledbTenancy\Commands\TenantCacheClearCommand;
use Roberts\LaravelSingledbTenancy\Events\TenantCreated;
use Roberts\LaravelSingledbTenancy\Http\Middleware\AuthorizePrimaryTenant;
use Roberts\LaravelSingledbTenancy\Listeners\CacheTenantsExist;
use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Middleware\TenantResolutionMiddleware;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Observers\TenantObserver;
use Roberts\LaravelSingledbTenancy\Resolvers\DomainResolver;
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
        $this->app->singleton(Services\TenantCache::class);
        $this->app->singleton(Services\DomainResolver::class);
        $this->app->singleton(Services\TenantRouteManager::class);
        $this->app->singleton(Services\SuperAdmin::class);
        $this->app->singleton(Services\SmartFallback::class);

        $this->app->bind('command.tenancy:aware', TenantAwareCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app['router']->aliasMiddleware('auth.primary', AuthorizePrimaryTenant::class);

        $this->app['events']->listen(
            TenantCreated::class,
            CacheTenantsExist::class,
        );

        // Register the TenantObserver
        Tenant::observe(TenantObserver::class);
    }
}
