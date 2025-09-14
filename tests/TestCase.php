<?php

namespace Roberts\LaravelSingledbTenancy\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Roberts\LaravelSingledbTenancy\LaravelSingledbTenancyServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Roberts\\LaravelSingledbTenancy\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelSingledbTenancyServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Load package migrations for testing
        $migration = include __DIR__.'/../database/migrations/create_tenants_table.php.stub';
        $migration->up();

        // Create posts table for testing
        $app['db']->connection()->getSchemaBuilder()->create('posts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }
}
