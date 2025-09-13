<?php

use Roberts\LaravelSingledbTenancy\Context\TenantContext;
use Roberts\LaravelSingledbTenancy\Exceptions\TenantNotResolvedException;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

describe('Helper Functions', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->create();
    });

    it('tenant_context returns TenantContext instance', function () {
        expect(tenant_context())->toBeInstanceOf(TenantContext::class);
    });

    it('current_tenant returns null when no tenant is set', function () {
        tenant_context()->clear();

        expect(current_tenant())->toBeNull();
    });

    it('current_tenant returns tenant when one is set', function () {
        tenant_context()->set($this->tenant);

        expect(current_tenant())->toBe($this->tenant);
    });

    it('current_tenant_id returns null when no tenant is set', function () {
        tenant_context()->clear();

        expect(current_tenant_id())->toBeNull();
    });

    it('current_tenant_id returns tenant id when one is set', function () {
        tenant_context()->set($this->tenant);

        expect(current_tenant_id())->toBe($this->tenant->id);
    });

    it('has_tenant returns false when no tenant is set', function () {
        tenant_context()->clear();

        expect(has_tenant())->toBeFalse();
    });

    it('has_tenant returns true when tenant is set', function () {
        tenant_context()->set($this->tenant);

        expect(has_tenant())->toBeTrue();
    });

    it('require_tenant throws exception when no tenant is set', function () {
        tenant_context()->clear();

        expect(fn () => require_tenant())
            ->toThrow(TenantNotResolvedException::class);
    });

    it('require_tenant returns tenant when one is set', function () {
        tenant_context()->set($this->tenant);

        expect(require_tenant())->toBe($this->tenant);
    });
});
