<?php

namespace Roberts\LaravelSingledbTenancy;

use Illuminate\Routing\Router;
use Roberts\LaravelSingledbTenancy\Commands\LaravelSingledbTenancyCommand;
use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Middleware\TenantResolutionMiddleware;
use Roberts\LaravelSingledbTenancy\Resolvers\DomainResolver;
use Roberts\LaravelSingledbTenancy\Resolvers\SubdomainResolver;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;
use Roberts\LaravelSingledbTenancy\Services\TenantRouteManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSingledbTenancyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-singledb-tenancy')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_tenants_table')
            ->hasCommand(LaravelSingledbTenancyCommand::class);
    }

    public function packageRegistered(): void
    {
        // Register the TenantContext as a singleton
        $this->app->singleton(TenantContext::class);

        // Register tenant resolution services
        $this->app->singleton(TenantCache::class);
        $this->app->singleton(DomainResolver::class);
        $this->app->singleton(SubdomainResolver::class);
        $this->app->singleton(TenantRouteManager::class);
    }

    public function packageBooted(): void
    {
        // Load helper functions
        if (file_exists(__DIR__.'/Helpers/Context.php')) {
            require_once __DIR__.'/Helpers/Context.php';
        }

        // Register middleware
        $this->registerMiddleware();
    }

    /**
     * Register the tenant resolution middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('tenant', TenantResolutionMiddleware::class);
    }
}
