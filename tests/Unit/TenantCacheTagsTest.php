<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Services\TenantCache;

it('uses cache tags when available', function () {
    $cache = app(TenantCache::class);

    // Test that getCacheTags returns the configured tags
    $reflection = new \ReflectionClass($cache);
    $method = $reflection->getMethod('getCacheTags');
    $method->setAccessible(true);
    $tags = $method->invoke($cache);

    expect($tags)->toBe(['tenant_resolution']);
});

it('gracefully handles cache stores without tag support', function () {
    // This test verifies that our cache methods don't throw exceptions
    // when the cache store doesn't support tags (like array driver in tests)

    $cache = app(TenantCache::class);

    // These should not throw exceptions even with array cache driver
    try {
        $cache->flush();
        $cache->flushAll();
        $cache->forgetTenantByDomain('example.com');

        // If we get here, no exceptions were thrown
        expect(true)->toBeTrue();
    } catch (Exception $e) {
        $this->fail("Cache methods should not throw exceptions: {$e->getMessage()}");
    }
});
