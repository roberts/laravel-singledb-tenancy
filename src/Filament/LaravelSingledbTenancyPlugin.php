<?php

namespace Roberts\LaravelSingledbTenancy\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class LaravelSingledbTenancyPlugin implements Plugin
{
    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'roberts-laravel-singledb-tenancy';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Roberts\\LaravelSingledbTenancy\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void
    {
        // no-op
    }
}
