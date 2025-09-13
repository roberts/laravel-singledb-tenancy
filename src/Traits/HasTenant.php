<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Scopes\TenantScope;

/**
 * Trait HasTenant
 *
 * This trait automatically:
 * - Applies the TenantScope to all queries
 * - Assigns the current tenant ID when creating models
 * - Provides a tenant relationship
 * - Allows for tenant column customization
 *
 * @mixin Model
 * @phpstan-ignore trait.unused
 */
trait HasTenant
{
    /**
     * Boot the HasTenant trait.
     */
    protected static function bootHasTenant(): void
    {
        // Add the TenantScope global scope
        static::addGlobalScope(new TenantScope);

        // Automatically set tenant_id when creating models
        static::creating(function (Model $model): void {
            $tenantColumn = $model->getTenantColumn();
            if (! $model->getAttribute($tenantColumn)) {
                $tenantId = current_tenant_id();
                if ($tenantId !== null) {
                    $model->setAttribute($tenantColumn, $tenantId);
                }
            }
        });
    }

    /**
     * Get the name of the tenant column.
     */
    public function getTenantColumn(): string
    {
        return $this->tenantColumn ?? config('singledb-tenancy.tenant_column', 'tenant_id');
    }

    /**
     * Get the fully qualified tenant column name.
     */
    public function getQualifiedTenantColumn(): string
    {
        return $this->qualifyColumn($this->getTenantColumn());
    }

    /**
     * Get the tenant that owns this model.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, $this->getTenantColumn());
    }

    /**
     * Scope a query to include models for all tenants.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope a query to only include models for the specified tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForTenant($query, int|Tenant $tenant)
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->getQualifiedTenantColumn(), $tenantId);
    }
}
