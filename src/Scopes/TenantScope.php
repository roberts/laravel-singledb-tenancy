<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Get TenantCache instance to check if tenants exist
        $tenantCache = app(TenantCache::class);
        
        // If no tenants exist in the system, don't apply any scoping
        if (! $tenantCache->tenantsExist()) {
            return;
        }

        $tenantId = current_tenant_id();

        if ($tenantId !== null) {
            $tenantColumn = $this->getTenantColumn($model);
            $builder->where($model->qualifyColumn($tenantColumn), $tenantId);
        } else {
            // When tenants exist but no tenant context is set, return no results by default
            // This enforces tenant isolation - data should only be accessible with proper tenant context
            $builder->whereRaw('1 = 0');
        }
    }

    /**
     * Get the tenant column name for the given model.
     */
    protected function getTenantColumn(Model $model): string
    {
        if (method_exists($model, 'getTenantColumn')) {
            return $model->getTenantColumn();
        }

        $column = config('singledb-tenancy.tenant_column', 'tenant_id');
        return is_string($column) ? $column : 'tenant_id';
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param Builder<Model> $builder
     */
    public function extend(Builder $builder): void
    {
        // We don't need to add macros here since we handle scopes in the trait
    }
}
