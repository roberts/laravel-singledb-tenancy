<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Services\TenantCache;

beforeEach(function () {
    $this->cache = app(TenantCache::class);
});

describe('Tenant Cache Tags', function () {
    describe('Tag Support', function () {
        it('uses cache tags when available', function () {
            $reflection = new \ReflectionClass($this->cache);
            $method = $reflection->getMethod('getCacheTags');
            $method->setAccessible(true);
            
            expect($method->invoke($this->cache))->toBe(['tenant_resolution']);
        });

        it('gracefully handles cache stores without tag support', function () {
            // Verify cache methods don't throw exceptions with array cache driver
            expect(function () {
                $this->cache->flush();
                $this->cache->flushAll(); 
                $this->cache->forgetTenantByDomain('example.com');
            })->not()->toThrow(Exception::class);
        });
    });
});
