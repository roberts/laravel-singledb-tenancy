<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Commands;

use Illuminate\Console\Command;
use Roberts\LaravelSingledbTenancy\Services\SmartFallback;

class CacheFallbackStatusCommand extends Command
{
    protected $signature = 'tenancy:cache-fallback-status';

    protected $description = 'Cache the tenancy fallback status based on whether tenants exist.';

    public function handle(SmartFallback $smartFallback): int
    {
        if ($smartFallback->isFallback()) {
            $this->info('No tenants found. Fallback mode is active.');
        } else {
            $this->info('Tenants found. Fallback mode is disabled and this status is now permanently cached.');
        }

        return self::SUCCESS;
    }
}
