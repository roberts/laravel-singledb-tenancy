<?php

use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Exceptions\TenantNotResolvedException;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

describe('TenantContext', function () {
    beforeEach(function () {
        // Use the global singleton instance for consistent behavior
        $this->context = app(\Roberts\LaravelSingledbTenancy\Context\TenantContext::class);
        $this->context->clear(); // Clear any previous state
        $this->tenant = Tenant::factory()->create();
    });

    afterEach(function () {
        // Clean up after each test
        $this->context->clear();
    });

    it('can set and get tenant', function () {
        $this->context->set($this->tenant);

        expect($this->context->get())->toBe($this->tenant)
            ->and($this->context->id())->toBe($this->tenant->id)
            ->and($this->context->has())->toBeTrue();
    });

    it('returns null when no tenant is set', function () {
        expect($this->context->get())->toBeNull()
            ->and($this->context->id())->toBeNull()
            ->and($this->context->has())->toBeFalse();
    });

    it('can clear tenant context', function () {
        $this->context->set($this->tenant);
        $this->context->clear();

        expect($this->context->get())->toBeNull()
            ->and($this->context->has())->toBeFalse();
    });

    it('throws exception when checking for required tenant but none is set', function () {
        expect(fn() => $this->context->check())
            ->toThrow(TenantNotResolvedException::class, 'No tenant is currently set in context');
    });

    it('returns tenant when checking and one is set', function () {
        $this->context->set($this->tenant);

        expect($this->context->check())->toBe($this->tenant);
    });

    it('can run callback with specific tenant context', function () {
        $otherTenant = Tenant::factory()->create();
        $this->context->set($this->tenant);

        $result = $this->context->runWith($otherTenant, function () {
            return current_tenant_id();
        });

        expect($result)->toBe($otherTenant->id)
            ->and($this->context->get())->toBe($this->tenant); // Original tenant restored
    });

    it('can run callback without tenant context', function () {
        $this->context->set($this->tenant);

        $result = $this->context->runWithout(function () {
            return current_tenant();
        });

        expect($result)->toBeNull()
            ->and($this->context->get())->toBe($this->tenant); // Original tenant restored
    });

    it('restores original context even if callback throws exception', function () {
        $otherTenant = Tenant::factory()->create();
        $this->context->set($this->tenant);

        try {
            $this->context->runWith($otherTenant, function () {
                throw new Exception('Test exception');
            });
        } catch (Exception $e) {
            // Expected
        }

        expect($this->context->get())->toBe($this->tenant); // Original tenant restored
    });
});
