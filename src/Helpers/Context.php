<?php

use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

if (! function_exists('tenant_context')) {
    /**
     * Get the tenant context instance.
     */
    function tenant_context(): TenantContext
    {
        return app(TenantContext::class);
    }
}

if (! function_exists('current_tenant')) {
    /**
     * Get the current tenant.
     */
    function current_tenant(): ?Tenant
    {
        return tenant_context()->get();
    }
}

if (! function_exists('current_tenant_id')) {
    /**
     * Get the current tenant ID.
     */
    function current_tenant_id(): ?int
    {
        return tenant_context()->id();
    }
}

if (! function_exists('has_tenant')) {
    /**
     * Check if a tenant is currently set in context.
     */
    function has_tenant(): bool
    {
        return tenant_context()->has();
    }
}

if (! function_exists('require_tenant')) {
    /**
     * Get the current tenant or throw an exception if none is set.
     *
     * @throws Roberts\LaravelSingledbTenancy\Exceptions\TenantNotResolvedException
     */
    function require_tenant(): Tenant
    {
        return tenant_context()->check();
    }
}
