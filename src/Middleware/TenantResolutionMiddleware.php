<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Events\TenantResolved;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Resolvers\DomainResolver;
use Roberts\LaravelSingledbTenancy\Services\SmartFallback;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class TenantResolutionMiddleware
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantCache $tenantCache,
        private DomainResolver $domainResolver,
        private SmartFallback $smartFallback,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->smartFallback->isFallback()) {
            return $next($request);
        }

        // Check for forced tenant, but only in non-production environments
        if (config('app.env') !== 'production') {
            if ($forcedTenant = $this->getForcedTenant()) {
                $this->tenantContext->set($forcedTenant);

                event(new TenantResolved($forcedTenant));

                return $next($request);
            }
        }

        // Smart Fallback Logic: Check if any tenants exist
        if (! $this->tenantCache->tenantsExist()) {
            // No tenants exist - check if user explicitly wants exception handling
            $handlingStrategy = config('singledb-tenancy.failure_handling.unresolved_tenant', 'continue');

            if ($handlingStrategy === 'exception') {
                throw new RuntimeException('Could not resolve tenant from request');
            }

            // Skip all tenant logic and run normally
            return $next($request);
        }

        // Try domain resolution
        $tenant = $this->domainResolver->resolve($request);

        if ($tenant) {
            if ($this->isTenantSuspended($tenant)) {
                abort(403, 'Tenant is suspended');
            }

            $this->tenantContext->set($tenant);

            event(new TenantResolved($tenant));

            return $next($request);
        }

        // No tenant resolved, try fallback to tenant ID 1
        if ($this->tenantCache->primaryTenantExists()) {
            $primaryTenant = $this->tenantCache->getPrimaryTenant();

            if ($primaryTenant && ! $this->isTenantSuspended($primaryTenant)) {
                $this->tenantContext->set($primaryTenant);

                return $next($request);
            }
        }

        // No tenant resolved and no primary tenant available
        return $this->handleUnresolvedTenant($request, $next);
    }

    /**
     * Get forced tenant for development/testing.
     */
    protected function getForcedTenant(): ?Tenant
    {
        $forcedDomain = config('singledb-tenancy.development.force_tenant');

        if (! $forcedDomain) {
            return null;
        }

        /** @var class-string<Tenant> $tenantModel */
        $tenantModel = config('singledb-tenancy.tenant_model');

        return $tenantModel::where('domain', $forcedDomain)->first();
    }

    /**
     * Check if tenant is suspended.
     */
    protected function isTenantSuspended(Tenant $tenant): bool
    {
        return ! $tenant->isActive();
    }

    /**
     * Handle case where no tenant could be resolved.
     */
    protected function handleUnresolvedTenant(Request $request, Closure $next): Response
    {
        $handling = config('singledb-tenancy.failure_handling.unresolved_tenant', 'continue');
        $redirectRoute = config('singledb-tenancy.failure_handling.redirect_route', 'home');

        return match ($handling) {
            'exception' => throw new \RuntimeException('Could not resolve tenant from request'),
            'redirect' => redirect()->route(is_string($redirectRoute) ? $redirectRoute : 'home'),
            default => $next($request), // continue without tenant context
        };
    }
}
