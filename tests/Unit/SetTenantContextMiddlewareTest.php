<?php

declare(strict_types=1);

use Illuminate\Foundation\Bus\Dispatchable;
use Roberts\LaravelSingledbTenancy\Jobs\Middleware\SetTenantContext;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

// Create a test job for testing the middleware
class SetTenantContextTestJob
{
    use Dispatchable;

    public $tenantId;
    public $executedTenantId;

    public function __construct()
    {
        $this->tenantId = current_tenant_id();
    }

    public function handle()
    {
        $this->executedTenantId = current_tenant_id();
    }

    public function middleware()
    {
        return [
            new SetTenantContext($this->tenantId),
        ];
    }
}

describe('SetTenantContext Job Middleware', function () {
    beforeEach(function () {
        $this->tenant1 = Tenant::factory()->create();
        $this->tenant2 = Tenant::factory()->create();
        
        // Clear tenant context before each test
        tenant_context()->clear();
    });

    it('sets tenant context from job tenant id', function () {
        tenant_context()->set($this->tenant1);
        
        $job = new SetTenantContextTestJob();
        $middleware = new SetTenantContext($this->tenant1->id);

        $middleware->handle($job, function ($job) {
            expect(current_tenant_id())->toBe($this->tenant1->id);
            $job->handle();
        });

        expect($job->executedTenantId)->toBe($this->tenant1->id);
    });

    it('handles null tenant id gracefully', function () {
        $job = new SetTenantContextTestJob();
        $middleware = new SetTenantContext(null);

        $middleware->handle($job, function ($job) {
            expect(current_tenant_id())->toBeNull();
            $job->handle();
        });

        expect($job->executedTenantId)->toBeNull();
    });

    it('handles non-existent tenant id gracefully', function () {
        $job = new SetTenantContextTestJob();
        $middleware = new SetTenantContext(99999); // Non-existent ID

        $middleware->handle($job, function ($job) {
            expect(current_tenant_id())->toBeNull();
            $job->handle();
        });

        expect($job->executedTenantId)->toBeNull();
    });

    it('restores original tenant context after job execution', function () {
        tenant_context()->set($this->tenant1);
        
        $job = new SetTenantContextTestJob();
        $middleware = new SetTenantContext($this->tenant2->id);

        expect(current_tenant_id())->toBe($this->tenant1->id);

        $middleware->handle($job, function ($job) {
            expect(current_tenant_id())->toBe($this->tenant2->id);
            $job->handle();
        });

        // Should restore original context
        expect(current_tenant_id())->toBe($this->tenant1->id);
    });

    it('works without original tenant context', function () {
        // Start with no tenant context
        expect(current_tenant_id())->toBeNull();
        
        $job = new SetTenantContextTestJob();
        $middleware = new SetTenantContext($this->tenant1->id);

        $middleware->handle($job, function ($job) {
            expect(current_tenant_id())->toBe($this->tenant1->id);
            $job->handle();
        });

        // Should return to null context
        expect(current_tenant_id())->toBeNull();
    });

    it('handles job exceptions properly while restoring context', function () {
        tenant_context()->set($this->tenant1);
        
        $job = new class {
            public function handle()
            {
                throw new \Exception('Job failed');
            }
        };

        $middleware = new SetTenantContext($this->tenant2->id);

        try {
            $middleware->handle($job, function ($job) {
                expect(current_tenant_id())->toBe($this->tenant2->id);
                $job->handle();
            });
        } catch (\Exception $e) {
            // Exception should be caught and context should be restored
            expect($e->getMessage())->toBe('Job failed');
        }

        // Original context should be restored even after exception
        expect(current_tenant_id())->toBe($this->tenant1->id);
    });
});
