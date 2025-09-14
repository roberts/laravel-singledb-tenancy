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
        if ($tenant) {
            $identifier = $this->getTenantRouteIdentifier($tenant);

            if ($this->cache->tenantHasCustomRoutes($identifier)) {
                $this->loadCustomTenantRoutes($identifier);

                return;
            }
        }

        $this->loadDefaultRoutes();
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
     * Uses the tenant's domain for route file names.
     */
    protected function getTenantRouteIdentifier(Tenant $tenant): string
    {
        return $tenant->domain;
    }

    /**
     * Get the full path to custom route file.
     */
    protected function getCustomRouteFilePath(string $identifier): string
    {
        return base_path("routes/tenants/{$identifier}.php");
    }

}
