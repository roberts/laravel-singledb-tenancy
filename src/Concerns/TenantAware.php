<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Concerns;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Roberts\LaravelSingledbTenancy\Jobs\Middleware\SetTenantContext;

trait TenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $tenantId;

    public function __construct()
    {
        $this->tenantId = current_tenant_id();
    }

    public function middleware(): array
    {
        return [new SetTenantContext($this->tenantId)];
    }
}
