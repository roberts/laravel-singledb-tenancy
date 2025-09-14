<?php

declare(strict_types=1);

// config for Roberts/LaravelSingledbTenancy
return [

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

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant resolution caching.
    |
    */
    'caching' => [
        /*
        | Enable caching for tenant resolution to improve performance.
        */
        'enabled' => env('TENANT_CACHE_ENABLED', true), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig

        /*
        | The cache store to use for tenant resolution.
        */
        'store' => env('TENANT_CACHE_STORE', 'array'), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig

        /*
        | Cache TTL in seconds.
        */
        'ttl' => env('TENANT_CACHE_TTL', 3600), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig

        /*
        | Cache key prefix for tenant resolution.
        */
        'key_prefix' => 'tenant_resolution:',

        /*
        | Cache tags for easier invalidation.
        */
        'tags' => ['tenant_resolution'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failure Handling
    |--------------------------------------------------------------------------
    |
    | How to handle cases where tenant resolution fails.
    |
    */
    'failure_handling' => [
        /*
        | What to do when tenant cannot be resolved.
        | Options: 'fallback', 'continue', 'exception', 'redirect'
        */
        'unresolved_tenant' => 'fallback',

        /*
        | Route to redirect to on failures.
        */
        'redirect_route' => 'home',
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | The email address of the user who has super admin privileges over the
    | tenancy system, allowing access to features like the Filament panel.
    |
    */
    'super_admin' => [
        'email' => env('TENANCY_SUPER_ADMIN_EMAIL'), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig
    ],

    /*
    |--------------------------------------------------------------------------
    | Development
    |--------------------------------------------------------------------------
    |
    | Development and testing configuration.
    |
    */
    'development' => [
        /*
        | Force a specific tenant domain for development/testing.
        */
        'force_tenant' => env('FORCE_TENANT_DOMAIN'), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig
    ],
];
