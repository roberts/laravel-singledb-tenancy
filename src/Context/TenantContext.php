<?php

namespace Roberts\LaravelSingledbTenancy\Context;

use Roberts\LaravelSingledbTenancy\Exceptions\TenantNotResolvedException;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class TenantContext
{
    private ?Tenant $tenant = null;

    /**
     * Set the current tenant.
     */
    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the current tenant.
     */
    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Get the current tenant ID.
     */
    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    /**
     * Check if a tenant is currently set and return it.
     *
     * @throws TenantNotResolvedException
     */
    public function check(): Tenant
    {
        if (! $this->tenant) {
            throw new TenantNotResolvedException('No tenant is currently set in context');
        }

        return $this->tenant;
    }

    /**
     * Clear the current tenant context.
     */
    public function clear(): void
    {
        $this->tenant = null;
    }

    /**
     * Check if a tenant is currently set.
     */
    public function has(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Execute a callback with a specific tenant context.
     */
    public function runWith(Tenant $tenant, callable $callback): mixed
    {
        $original = $this->tenant;

        try {
            $this->set($tenant);

            return $callback();
        } finally {
            if ($original) {
                $this->set($original);
            } else {
                $this->clear();
            }
        }
    }

    /**
     * Execute a callback without any tenant context.
     */
    public function runWithout(callable $callback): mixed
    {
        $original = $this->tenant;

        try {
            $this->clear();

            return $callback();
        } finally {
            if ($original) {
                $this->set($original);
            }
        }
    }
}
