<?php

namespace Roberts\LaravelSingledbTenancy\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class AddTenantColumnCommand extends Command
{
    public $signature = 'tenancy:add-column 
                        {table : The table to add the tenant_id column to}
                        {--foreign : Add foreign key constraint to tenants table}
                        {--index : Add database index to tenant_id column}
                        {--nullable : Make the tenant_id column nullable}';

    public $description = 'Generate a migration to add tenant_id column to an existing table';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $table = $this->argument('table');
        $tenantColumn = config('singledb-tenancy.tenant_column', 'tenant_id');

        // Ensure we have string values
        if (! is_string($table)) {
            $this->error('Table name must be a string.');

            return self::FAILURE;
        }

        if (! is_string($tenantColumn)) {
            $this->error('Tenant column name must be a string.');

            return self::FAILURE;
        }

        // Validate table name
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            $this->error('Invalid table name. Table names must contain only letters, numbers, and underscores.');

            return self::FAILURE;
        }

        // Generate migration name and filename
        $migrationName = 'add_'.$tenantColumn.'_to_'.$table.'_table';
        $className = Str::studly($migrationName);
        $filename = date('Y_m_d_His').'_'.$migrationName.'.php';

        // Check if migration already exists
        $migrationPath = database_path('migrations');
        $existingMigrations = glob($migrationPath.'/*_'.$migrationName.'.php');

        if (! empty($existingMigrations)) {
            $this->error("Migration for adding {$tenantColumn} to {$table} table already exists.");

            return self::FAILURE;
        }

        // Generate migration content
        $migrationContent = $this->generateMigrationContent($className, $table, $tenantColumn);

        // Write migration file
        $migrationFilePath = $migrationPath.'/'.$filename;

        if (! $this->files->isDirectory($migrationPath)) {
            $this->files->makeDirectory($migrationPath, 0755, true);
        }

        $this->files->put($migrationFilePath, $migrationContent);

        $this->info('Migration created successfully:');
        $this->line("  {$migrationFilePath}");
        $this->newLine();
        $this->comment('Next steps:');
        $this->line('  1. Review the generated migration file');
        $this->line('  2. Run: php artisan migrate');
        $this->line("  3. Add the HasTenant trait to your {$table} model");

        return self::SUCCESS;
    }

    protected function generateMigrationContent(string $className, string $table, string $tenantColumn): string
    {
        $addForeign = $this->option('foreign');
        $addIndex = $this->option('index');
        $nullable = $this->option('nullable');

        $columnDefinition = $nullable ? '->nullable()' : '';
        $indexDefinition = $addIndex ? "\n            \$table->index('{$tenantColumn}');" : '';
        $foreignDefinition = $addForeign ? "\n            \$table->foreign('{$tenantColumn}')->references('id')->on('tenants');" : '';

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->foreignId('{$tenantColumn}'){$columnDefinition};{$indexDefinition}{$foreignDefinition}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropForeign(['{$tenantColumn}']);
            \$table->dropColumn('{$tenantColumn}');
        });
    }
};
PHP;
    }
}
