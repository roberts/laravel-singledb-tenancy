<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class TenantCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Tenant $tenant
    ) {}
}
