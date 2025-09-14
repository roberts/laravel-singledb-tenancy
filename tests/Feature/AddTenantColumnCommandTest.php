<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->filesystem = new Filesystem;
    $this->migrationPath = database_path('migrations');
    $this->testPattern = $this->migrationPath.'/*_add_tenant_id_to_test_table_table.php';

    // Clean up any existing test migrations
    foreach (glob($this->testPattern) as $migration) {
        $this->filesystem->delete($migration);
    }
});

afterEach(function () {
    // Clean up test migrations
    foreach (glob($this->testPattern) as $migration) {
        $this->filesystem->delete($migration);
    }
});

describe('AddTenantColumnCommand', function () {
    describe('Migration Generation', function () {
        it('creates migration file with basic tenant_id column', function () {
            $this->artisan('tenancy:add-column test_table')
                ->expectsOutputToContain('Migration created successfully')
                ->assertExitCode(0);

            $files = glob($this->testPattern);
            expect($files)->toHaveCount(1);

            $content = $this->filesystem->get($files[0]);
            expect($content)
                ->toContain('Schema::table(\'test_table\'')
                ->toContain('$table->foreignId(\'tenant_id\')');
        });

        it('creates migration file with foreign key constraint', function () {
            $this->artisan('tenancy:add-column test_table --foreign')
                ->expectsOutputToContain('Migration created successfully')
                ->assertExitCode(0);

            $files = glob($this->testPattern);
            $content = $this->filesystem->get($files[0]);

            expect($content)->toContain('$table->foreign(\'tenant_id\')->references(\'id\')->on(\'tenants\')');
        });

        it('creates migration file with nullable column', function () {
            $this->artisan('tenancy:add-column test_table --nullable')
                ->expectsOutputToContain('Migration created successfully')
                ->assertExitCode(0);

            $files = glob($this->testPattern);
            $content = $this->filesystem->get($files[0]);

            expect($content)->toContain('->nullable()');
        });

        it('creates migration file with index', function () {
            $this->artisan('tenancy:add-column test_table --index')
                ->expectsOutputToContain('Migration created successfully')
                ->assertExitCode(0);

            $files = glob($this->testPattern);
            $content = $this->filesystem->get($files[0]);

            expect($content)->toContain('$table->index(\'tenant_id\')');
        });

        it('creates migration file with all options', function () {
            $this->artisan('tenancy:add-column test_table --foreign --nullable --index')
                ->expectsOutputToContain('Migration created successfully')
                ->assertExitCode(0);

            $files = glob($this->testPattern);
            $content = $this->filesystem->get($files[0]);

            expect($content)
                ->toContain('->nullable()')
                ->toContain('$table->foreign(\'tenant_id\')')
                ->toContain('$table->index(\'tenant_id\')');
        });
    });

    describe('Error Handling', function () {
        it('fails with invalid table name', function () {
            $this->artisan('tenancy:add-column ""')
                ->expectsOutputToContain('Invalid table name')
                ->assertExitCode(1);
        });

        it('fails when migration already exists', function () {
            // Create first migration
            $this->artisan('tenancy:add-column test_table')->assertExitCode(0);

            // Try to create again
            $this->artisan('tenancy:add-column test_table')
                ->expectsOutputToContain('Migration for adding tenant_id to test_table table already exists')
                ->assertExitCode(1);
        });
    });
});
