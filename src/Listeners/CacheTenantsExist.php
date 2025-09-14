<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Listeners;

use Roberts\LaravelSingledbTenancy\Services\SmartFallback;

class CacheTenantsExist
{
    public function __construct(protected SmartFallback $smartFallback)
    {
    }

    public function handle(): void
    {
        $this->smartFallback->permanentlyCacheTenantsExist();
    }
}
