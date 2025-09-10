<?php

namespace Roberts\LaravelSingledbTenancy;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Roberts\LaravelSingledbTenancy\Commands\LaravelSingledbTenancyCommand;

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
            ->hasMigration('create_laravel_singledb_tenancy_table')
            ->hasCommand(LaravelSingledbTenancyCommand::class);
    }
}
