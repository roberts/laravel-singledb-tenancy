<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Roberts\LaravelSingledbTenancy\Middleware\AuthorizePrimaryTenant;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Tests\TestCase;

class AuthorizePrimaryTenantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/protected-route', fn () => 'OK')
            ->middleware(AuthorizePrimaryTenant::class);
    }

    /** @test */
    public function it_allows_access_when_the_current_tenant_is_the_primary_tenant()
    {
        $primaryTenant = Tenant::factory()->create(['id' => 1]);
        tenant_context()->set($primaryTenant);

        $this->get('/_test/protected-route')->assertOk();
    }

    /** @test */
    public function it_aborts_with_404_when_the_current_tenant_is_not_the_primary_tenant()
    {
        $otherTenant = Tenant::factory()->create(['id' => 2]);
        tenant_context()->set($otherTenant);

        $this->get('/_test/protected-route')->assertNotFound();
    }

    /** @test */
    public function it_aborts_with_404_when_no_tenant_is_set()
    {
        tenant_context()->clear();

        $this->get('/_test/protected-route')->assertNotFound();
    }
}
