<?php

namespace Roberts\LaravelSingledbTenancy;

use Roberts\LaravelSingledbTenancy\Commands\LaravelSingledbTenancyCommand;
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
}
