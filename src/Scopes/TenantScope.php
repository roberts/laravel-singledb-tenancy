<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = current_tenant_id();

        if ($tenantId !== null) {
            $builder->where($model->getQualifiedTenantColumn(), $tenantId);
        } else {
            // When no tenant context is set, return no results by default
            // This enforces tenant isolation - data should only be accessible with proper tenant context
            $builder->whereRaw('1 = 0');
        }
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        // We don't need to add macros here since we handle scopes in the trait
    }
}
