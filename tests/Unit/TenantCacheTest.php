<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

beforeEach(function () {
    $this->cache = app(TenantCache::class);
    $this->cache->flush();
});

describe('Tenant Cache Service', function () {
    describe('Domain Resolution Caching', function () {
        it('caches tenant resolution by domain', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);

            $resolved1 = $this->cache->getTenantByDomain('example.test');
            expect($resolved1)->not()->toBeNull()->and($resolved1->id)->toBe($tenant->id);

            $resolved2 = $this->cache->getTenantByDomain('example.test');
            expect($resolved2->id)->toBe($tenant->id);
        });

        it('returns null when tenant not found by domain', function () {
            expect($this->cache->getTenantByDomain('nonexistent.test'))->toBeNull();
        });

        it('works when caching is disabled', function () {
            config(['singledb-tenancy.caching.enabled' => false]);
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);

            $resolved = $this->cache->getTenantByDomain('example.test');
            expect($resolved)->not()->toBeNull()->and($resolved->id)->toBe($tenant->id);
        });
    });

    describe('Cache Management', function () {
        it('forgets specific tenant cache', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);
            $this->cache->getTenantByDomain('example.test');
            $this->cache->forgetTenant($tenant);

            $resolved = $this->cache->getTenantByDomain('example.test');
            expect($resolved)->not()->toBeNull();
        });
    });

    describe('Custom Routes Support', function () {
        it('checks custom route file existence', function () {
            config(['singledb-tenancy.caching.enabled' => false]);

            $routesPath = storage_path('framework/testing/tenant-routes');
            $filePath = "{$routesPath}/example.com.php";

            if (! is_dir($routesPath)) {
                mkdir($routesPath, 0755, true);
            }
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            config(['singledb-tenancy.routing.custom_routes_path' => $routesPath]);

            expect(file_exists($filePath))->toBeFalse()
                ->and($this->cache->tenantHasCustomRoutes('example.com'))->toBeFalse();

            file_put_contents($filePath, "<?php\n// Test route file\n");

            expect(file_exists($filePath))->toBeTrue()
                ->and($this->cache->tenantHasCustomRoutes('example.com'))->toBeTrue();

            // Cleanup
            unlink($filePath);
            if (is_dir($routesPath)) {
                rmdir($routesPath);
            }
        });
    });
});
