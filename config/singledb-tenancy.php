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

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution
    |--------------------------------------------------------------------------
    |
    | Configuration for how tenants are resolved from HTTP requests.
    |
    */
    'resolution' => [
        /*
        | Resolution strategies to attempt in order.
        | Available: 'domain', 'subdomain'
        */
        'strategies' => ['domain', 'subdomain'],

        /*
        | Domain-based resolution matches the full request domain
        | against the tenant's domain column.
        */
        'domain' => [
            'enabled' => true,
            'column' => 'domain',
        ],

        /*
        | Subdomain-based resolution extracts the subdomain and matches
        | it against the tenant's slug column.
        */
        'subdomain' => [
            'enabled' => true,
            'column' => 'slug',
            'base_domain' => env('TENANT_BASE_DOMAIN', 'localhost'), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig
            'reserved' => ['api', 'admin', 'mail', 'www'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-specific routing.
    |
    */
    'routing' => [
        /*
        | Path where tenant-specific route files are stored.
        | Files should be named using the tenant's slug (e.g., 'acme.php').
        */
        'custom_routes_path' => base_path('routes/tenants'),

        /*
        | Whether to include the default web.php routes when a tenant
        | has custom route files.
        */
        'include_default_routes' => true,

        /*
        | The tenant attribute to use for route file naming.
        | Options: 'slug', 'id', 'domain'
        */
        'route_file_naming' => 'slug',
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
    | Development
    |--------------------------------------------------------------------------
    |
    | Development and testing configuration.
    |
    */
    'development' => [
        /*
        | Local development domains that should be treated specially.
        */
        'local_domains' => ['.test', '.local', '.localhost'],

        /*
        | Force a specific tenant slug for development/testing.
        */
        'force_tenant' => env('FORCE_TENANT_SLUG'), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig
    ],
];
