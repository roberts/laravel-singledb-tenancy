<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Services;

use Illuminate\Routing\Router;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class TenantRouteManager
{
    public function __construct(
        private Router $router,
        private TenantCache $cache
    ) {}

    /**
     * Load routes for the given tenant.
     */
    public function loadTenantRoutes(?Tenant $tenant): void
    {
        if (! $tenant) {
            $this->loadDefaultRoutes();

            return;
        }

        $identifier = $this->getTenantRouteIdentifier($tenant);

        if ($this->cache->tenantHasCustomRoutes($identifier)) {
            $this->loadCustomTenantRoutes($identifier);

            if ($this->shouldIncludeDefaultRoutes()) {
                $this->loadDefaultRoutes();
            }
        } else {
            $this->loadDefaultRoutes();
        }
    }

    /**
     * Load custom route file for tenant.
     */
    protected function loadCustomTenantRoutes(string $identifier): void
    {
        $routeFile = $this->getCustomRouteFilePath($identifier);

        if (file_exists($routeFile)) {
            $this->router->group([
                'middleware' => ['web'],
                'namespace' => null,
            ], $routeFile);
        }
    }

    /**
     * Load default web routes.
     */
    protected function loadDefaultRoutes(): void
    {
        $defaultRoutesFile = base_path('routes/web.php');

        if (file_exists($defaultRoutesFile)) {
            $this->router->group([
                'middleware' => ['web'],
                'namespace' => null,
            ], $defaultRoutesFile);
        }
    }

    /**
     * Get the tenant identifier for route file naming.
     */
    protected function getTenantRouteIdentifier(Tenant $tenant): string
    {
        $naming = config('singledb-tenancy.routing.route_file_naming', 'slug');

        return match ($naming) {
            'id' => (string) $tenant->id,
            'domain' => $tenant->domain ?? $tenant->slug,
            default => $tenant->slug,
        };
    }

    /**
     * Get the full path to custom route file.
     */
    protected function getCustomRouteFilePath(string $identifier): string
    {
        $routesPath = config('singledb-tenancy.routing.custom_routes_path', '');
        $routesPathStr = is_string($routesPath) ? $routesPath : '';

        return "{$routesPathStr}/{$identifier}.php";
    }

    /**
     * Check if default routes should be included with custom routes.
     */
    protected function shouldIncludeDefaultRoutes(): bool
    {
        return (bool) config('singledb-tenancy.routing.include_default_routes', true);
    }
}
