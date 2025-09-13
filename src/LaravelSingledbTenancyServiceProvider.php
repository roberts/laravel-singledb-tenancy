<?php

namespace Roberts\LaravelSingledbTenancy;

use Roberts\LaravelSingledbTenancy\Commands\LaravelSingledbTenancyCommand;
use Roberts\LaravelSingledbTenancy\Context\TenantContext;
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
    }

    public function packageBooted(): void
    {
        // Load helper functions
        if (file_exists(__DIR__.'/Helpers/Context.php')) {
            require_once __DIR__.'/Helpers/Context.php';
        }
    }
}
