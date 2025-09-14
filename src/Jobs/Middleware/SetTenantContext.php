<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Jobs\Middleware;

use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class SetTenantContext
{
    public function __construct(
        private ?int $tenantId
    ) {}

    public function handle($job, $next)
    {
        if (! $this->tenantId) {
            return $next($job);
        }

        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            return $next($job);
        }

        app(TenantContext::class)->runWith($tenant, fn () => $next($job));
    }
}
