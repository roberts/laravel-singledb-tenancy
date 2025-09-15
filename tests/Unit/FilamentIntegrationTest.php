<?php

use Roberts\LaravelSingledbTenancy\Filament\LaravelSingledbTenancyPlugin;

it('can instantiate filament plugin', function () {
    $plugin = LaravelSingledbTenancyPlugin::make();
    
    expect($plugin)->toBeInstanceOf(LaravelSingledbTenancyPlugin::class);
    expect($plugin->getId())->toBe('roberts-laravel-singledb-tenancy');
});

it('plugin has correct plugin id', function () {
    $plugin = LaravelSingledbTenancyPlugin::make();
    
    expect($plugin->getId())->toBe('roberts-laravel-singledb-tenancy');
});
