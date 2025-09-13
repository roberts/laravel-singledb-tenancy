<?php

declare(strict_types=1);

// config for Roberts/LaravelSingledbTenancy
return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Column
    |--------------------------------------------------------------------------
    |
    | The column name used to store the tenant ID in tenant-aware models.
    | This will be used by the HasTenant trait to automatically scope queries
    | and assign tenant IDs when creating new models.
    |
    */
    'tenant_column' => 'tenant_id',

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model that represents a tenant. This model should extend
    | the base Tenant model or implement the necessary tenant functionality.
    |
    */
    'tenant_model' => \Roberts\LaravelSingledbTenancy\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Tenant Context
    |--------------------------------------------------------------------------
    |
    | Configuration for how the tenant context behaves.
    |
    */
    'context' => [
        /*
        | Automatically resolve tenant from the current request's domain.
        | When enabled, the middleware will attempt to set the tenant context
        | based on the request domain.
        */
        'auto_resolve_from_domain' => true,

        /*
        | Throw an exception when attempting to access tenant data without
        | a tenant context being set. This helps catch bugs where tenant
        | isolation might be bypassed.
        */
        'strict_mode' => false,
    ],
];
