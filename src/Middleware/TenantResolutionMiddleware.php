<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Resolvers\DomainResolver;
use Roberts\LaravelSingledbTenancy\Resolvers\SubdomainResolver;
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
        private TenantContext $tenantContext
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

        // No tenant resolved
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
     */
    protected function getDefaultStrategies(): array
    {
        return config('singledb-tenancy.resolution.strategies', ['domain', 'subdomain']);
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

        return match ($handling) {
            'redirect' => redirect()->route(config('singledb-tenancy.failure_handling.redirect_route', 'home')),
            'block' => abort(403, 'Tenant is suspended'),
            default => response()->view(config('singledb-tenancy.failure_handling.suspended_view', 'tenant.suspended'), [
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

        return match ($handling) {
            'exception' => throw new \RuntimeException('Could not resolve tenant from request'),
            'redirect' => redirect()->route(config('singledb-tenancy.failure_handling.redirect_route', 'home')),
            default => $next($request), // continue without tenant context
        };
    }
}
