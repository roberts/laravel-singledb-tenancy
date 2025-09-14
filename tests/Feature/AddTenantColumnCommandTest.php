<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->filesystem = new Filesystem;
    $this->migrationPath = database_path('migrations');

    // Clean up any existing test migrations
    $testMigrations = glob($this->migrationPath.'/*_add_tenant_id_to_test_table_table.php');
    foreach ($testMigrations as $migration) {
        $this->filesystem->delete($migration);
    }
});

afterEach(function () {
    // Clean up test migrations
    $testMigrations = glob($this->migrationPath.'/*_add_tenant_id_to_test_table_table.php');
    foreach ($testMigrations as $migration) {
        $this->filesystem->delete($migration);
    }
});

it('creates migration file with basic tenant_id column', function () {
    $this->artisan('tenancy:add-column test_table')
        ->expectsOutputToContain('Migration created successfully')
        ->assertExitCode(0);

    $migrationFiles = glob($this->migrationPath.'/*_add_tenant_id_to_test_table_table.php');
    expect($migrationFiles)->toHaveCount(1);

    $migrationContent = $this->filesystem->get($migrationFiles[0]);
    expect($migrationContent)
        ->toContain('Schema::table(\'test_table\'')
        ->toContain('$table->foreignId(\'tenant_id\')');
});

it('creates migration file with foreign key constraint', function () {
    $this->artisan('tenancy:add-column test_table --foreign')
        ->expectsOutputToContain('Migration created successfully')
        ->assertExitCode(0);

    $migrationFiles = glob($this->migrationPath.'/*_add_tenant_id_to_test_table_table.php');
    $migrationContent = $this->filesystem->get($migrationFiles[0]);

    expect($migrationContent)
        ->toContain('$table->foreign(\'tenant_id\')->references(\'id\')->on(\'tenants\')');
});

it('creates migration file with index', function () {
    $this->artisan('tenancy:add-column test_table --index')
        ->expectsOutputToContain('Migration created successfully')
        ->assertExitCode(0);

    $migrationFiles = glob($this->migrationPath.'/*_add_tenant_id_to_test_table_table.php');
    $migrationContent = $this->filesystem->get($migrationFiles[0]);

    expect($migrationContent)
        ->toContain('$table->index(\'tenant_id\')');
});

it('creates migration file with nullable column', function () {
    $this->artisan('tenancy:add-column test_table --nullable')
        ->expectsOutputToContain('Migration created successfully')
        ->assertExitCode(0);

    $migrationFiles = glob($this->migrationPath.'/*_add_tenant_id_to_test_table_table.php');
    $migrationContent = $this->filesystem->get($migrationFiles[0]);

    expect($migrationContent)
        ->toContain('$table->foreignId(\'tenant_id\')->nullable()');
});

it('creates migration file with all options', function () {
    $this->artisan('tenancy:add-column test_table --foreign --index --nullable')
        ->expectsOutputToContain('Migration created successfully')
        ->assertExitCode(0);

    $migrationFiles = glob($this->migrationPath.'/*_add_tenant_id_to_test_table_table.php');
    $migrationContent = $this->filesystem->get($migrationFiles[0]);

    expect($migrationContent)
        ->toContain('$table->foreignId(\'tenant_id\')->nullable()')
        ->toContain('$table->index(\'tenant_id\')')
        ->toContain('$table->foreign(\'tenant_id\')->references(\'id\')->on(\'tenants\')');
});

it('fails with invalid table name', function () {
    $this->artisan('tenancy:add-column 123invalid')
        ->expectsOutputToContain('Invalid table name')
        ->assertExitCode(1);
});

it('fails when migration already exists', function () {
    // Create first migration
    $this->artisan('tenancy:add-column test_table')->assertExitCode(0);

    // Try to create duplicate
    $this->artisan('tenancy:add-column test_table')
        ->expectsOutputToContain('Migration for adding tenant_id to test_table table already exists')
        ->assertExitCode(1);
});

it('includes proper down method for rollback', function () {
    $this->artisan('tenancy:add-column test_table --foreign')
        ->assertExitCode(0);

    $migrationFiles = glob($this->migrationPath.'/*_add_tenant_id_to_test_table_table.php');
    $migrationContent = $this->filesystem->get($migrationFiles[0]);

    expect($migrationContent)
        ->toContain('public function down(): void')
        ->toContain('$table->dropForeign([\'tenant_id\'])')
        ->toContain('$table->dropColumn(\'tenant_id\')');
});
