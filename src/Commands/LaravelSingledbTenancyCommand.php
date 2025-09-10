<?php

namespace Roberts\LaravelSingledbTenancy\Commands;

use Illuminate\Console\Command;

class LaravelSingledbTenancyCommand extends Command
{
    public $signature = 'laravel-singledb-tenancy';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
