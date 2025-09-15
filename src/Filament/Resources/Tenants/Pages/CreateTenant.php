<?php

namespace Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Pages;

use Filament\Resources\Pages\CreateRecord;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\TenantResource;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
