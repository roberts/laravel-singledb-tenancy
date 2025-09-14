<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Resolvers\DomainResolver;
use Roberts\LaravelSingledbTenancy\Resolvers\SubdomainResolver;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class TenantResolutionMiddleware
{
    /**
     * @var array<string, class-string>
     */
    protected array $resolvers = [
        'domain' => DomainResolver::class,
        'subdomain' => SubdomainResolver::class,
    ];

    public function __construct(
        private TenantContext $tenantContext,
        private TenantCache $tenantCache
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$strategies): Response
    {
        // Check for forced tenant in development
        if ($forcedTenant = $this->getForcedTenant()) {
            $this->tenantContext->set($forcedTenant);

            return $next($request);
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

        // Determine which strategies to use
        $strategiesToUse = empty($strategies)
            ? $this->getDefaultStrategies()
            : $strategies;

        // Try each resolution strategy in order
        foreach ($strategiesToUse as $strategy) {
            $tenant = $this->resolveWithStrategy($strategy, $request);

            if ($tenant) {
                if ($this->isTenantSuspended($tenant)) {
                    return $this->handleSuspendedTenant($tenant, $request);
                }

                $this->tenantContext->set($tenant);

                return $next($request);
            }
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
     * Resolve tenant using specific strategy.
     */
    protected function resolveWithStrategy(string $strategy, Request $request): ?Tenant
    {
        if (! isset($this->resolvers[$strategy])) {
            return null;
        }

        $resolverClass = $this->resolvers[$strategy];
        $resolver = app($resolverClass);

        return $resolver->resolve($request);
    }

    /**
     * Get default resolution strategies from config.
     *
     * @return array<string>
     */
    protected function getDefaultStrategies(): array
    {
        $strategies = config('singledb-tenancy.resolution.strategies', ['domain', 'subdomain']);
        
        if (! is_array($strategies)) {
            return ['domain', 'subdomain'];
        }
        
        /** @var array<string> */
        return array_filter($strategies, 'is_string');
    }

    /**
     * Get forced tenant for development/testing.
     */
    protected function getForcedTenant(): ?Tenant
    {
        $forcedSlug = config('singledb-tenancy.development.force_tenant');

        if (! $forcedSlug) {
            return null;
        }

        /** @var class-string<Tenant> $tenantModel */
        $tenantModel = config('singledb-tenancy.tenant_model');

        return $tenantModel::where('slug', $forcedSlug)->first();
    }

    /**
     * Check if tenant is suspended.
     */
    protected function isTenantSuspended(Tenant $tenant): bool
    {
        return ! $tenant->isActive();
    }

    /**
     * Handle suspended tenant.
     */
    protected function handleSuspendedTenant(Tenant $tenant, Request $request): Response
    {
        $handling = config('singledb-tenancy.failure_handling.suspended_tenant', 'show_page');
        
        $redirectRoute = config('singledb-tenancy.failure_handling.redirect_route', 'home');
        $suspendedView = config('singledb-tenancy.failure_handling.suspended_view', 'tenant.suspended');

        return match ($handling) {
            'redirect' => redirect()->route(is_string($redirectRoute) ? $redirectRoute : 'home'),
            'block' => abort(403, 'Tenant is suspended'),
            default => response()->view(is_string($suspendedView) ? $suspendedView : 'tenant.suspended', [
                'tenant' => $tenant,
            ], 503),
        };
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
