<?php

namespace Roberts\LaravelSingledbTenancy\Tests\Feature\Filament;

use Roberts\LaravelSingledbTenancy\Filament\LaravelSingledbTenancyPlugin;
use Roberts\LaravelSingledbTenancy\Tests\TestCase;

class PluginTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $plugin = LaravelSingledbTenancyPlugin::make();

        $this->assertInstanceOf(LaravelSingledbTenancyPlugin::class, $plugin);
        $this->assertEquals('roberts-laravel-singledb-tenancy', $plugin->getId());
    }
}
