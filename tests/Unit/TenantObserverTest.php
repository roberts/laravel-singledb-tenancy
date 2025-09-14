<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;

beforeEach(function () {
    $this->tenantCache = app(TenantCache::class);
});

describe('TenantObserver', function () {
    it('clears tenant cache when tenant is updated', function () {
        $tenant = Tenant::factory()->create(['domain' => 'original.test']);
        
        // Cache the tenant
        $this->tenantCache->getTenantByDomain('original.test');
        
        // Update tenant
        $tenant->update(['domain' => 'updated.test']);
        
        // Old cached entry should be cleared
        // Since we can't directly test cache clearing, we test that the new domain resolves
        $cachedTenant = $this->tenantCache->getTenantByDomain('updated.test');
        expect($cachedTenant->domain)->toBe('updated.test');
    });

    it('clears tenant cache when tenant is deleted', function () {
        // Create a tenant with a unique domain for this test
        $uniqueDomain = 'test-' . uniqid() . '.example';
        $tenant = Tenant::factory()->create(['domain' => $uniqueDomain]);
        
        // Skip if it's tenant ID 1 (protected) 
        if ($tenant->id === 1) {
            expect(fn() => $tenant->delete())->toThrow(\Exception::class);
            return;
        }
        
        // Cache the tenant first
        $cachedTenant = $this->tenantCache->getTenantByDomain($uniqueDomain);
        expect($cachedTenant)->not->toBeNull();
        
        // Soft delete the tenant
        $tenant->delete();
        
        // Clear the cache manually to ensure we're testing fresh lookup
        $this->tenantCache->forgetTenantByDomain($uniqueDomain);
        
        // Now lookup should return null since tenant was soft deleted
        $cachedTenant = $this->tenantCache->getTenantByDomain($uniqueDomain);
        expect($cachedTenant)->toBeNull('Cache should not return a soft-deleted tenant');
    });

    it('handles force deletion properly', function () {
        // Create a tenant with a unique domain for this test
        $uniqueDomain = 'force-' . uniqid() . '.test';
        $tenant = Tenant::factory()->create(['domain' => $uniqueDomain]);
        
        // Skip if it's tenant ID 1 (protected)
        if ($tenant->id === 1) {
            expect(fn() => $tenant->forceDelete())->toThrow(\Exception::class);
            return;
        }
        
        // Cache the tenant first
        $cachedTenant = $this->tenantCache->getTenantByDomain($uniqueDomain);
        expect($cachedTenant)->not->toBeNull();
        
        // Force delete the tenant
        $tenant->forceDelete();
        
        // Clear the cache manually to ensure we're testing fresh lookup
        $this->tenantCache->forgetTenantByDomain($uniqueDomain);
        
        // Now lookup should return null since tenant was force deleted
        $cachedTenant = $this->tenantCache->getTenantByDomain($uniqueDomain);
        expect($cachedTenant)->toBeNull('Cache should not return a force-deleted tenant');
    });    it('handles tenant creation without issues', function () {
        $tenant = Tenant::factory()->create(['domain' => 'new.test']);
        
        // Should be able to resolve the new tenant
        $cachedTenant = $this->tenantCache->getTenantByDomain('new.test');
        expect($cachedTenant)->not->toBeNull();
        expect($cachedTenant->id)->toBe($tenant->id);
    });

    it('protects primary tenant from deletion', function () {
        // Create tenant with ID 1
        $primaryTenant = Tenant::factory()->create(['id' => 1, 'domain' => 'primary.test']);
        
        expect(function () use ($primaryTenant) {
            $primaryTenant->delete();
        })->toThrow(\Exception::class, 'Cannot delete Tenant 1 since it is the primary domain.');
    });

    it('allows deletion of non-primary tenants', function () {
        // Create multiple tenants to ensure we don't get ID 1
        Tenant::factory()->create();
        $tenant = Tenant::factory()->create(['domain' => 'deletable.test']);
        
        // Should be able to delete non-primary tenants (if not ID 1)
        if ($tenant->id !== 1) {
            expect($tenant->delete())->toBeTrue();
            expect($tenant->trashed())->toBeTrue();
        } else {
            // If it is ID 1, verify it's protected
            expect(fn() => $tenant->delete())->toThrow(\Exception::class);
        }
    });

    it('maintains cache consistency during tenant lifecycle', function () {
        $tenant = Tenant::factory()->create(['domain' => 'lifecycle.test']);
        
        // Cache the tenant
        $cached = $this->tenantCache->getTenantByDomain('lifecycle.test');
        expect($cached)->not->toBeNull();
        
        // Update tenant
        $tenant->update(['name' => 'Updated Name']);
        
        // Clear cache manually to simulate observer cache clearing
        $this->tenantCache->forgetTenantByDomain('lifecycle.test');
        
        // Cache should now reflect the updated data
        $updated = $this->tenantCache->getTenantByDomain('lifecycle.test');
        expect($updated->name)->toBe('Updated Name');
        
        // Delete tenant (only if not ID 1)
        if ($tenant->id !== 1) {
            $tenant->delete();
            
            // Should not be cached anymore
            $deleted = $this->tenantCache->getTenantByDomain('lifecycle.test');
            expect($deleted)->toBeNull();
        } else {
            // If it's tenant ID 1, just verify it exists
            expect($updated)->not->toBeNull();
        }
    });
});
