<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Tests\Unit;

use Illuminate\Foundation\Auth\User;
use Roberts\LaravelSingledbTenancy\Services\SuperAdmin;
use Roberts\LaravelSingledbTenancy\Tests\TestCase;

class SuperAdminTest extends TestCase
{
    private SuperAdmin $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = app(SuperAdmin::class);
    }

    /** @test */
    public function it_returns_true_for_the_super_admin_user()
    {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        $user = new User;
        $user->email = 'super@admin.com';

        $this->assertTrue($this->superAdmin->is($user));
    }

    /** @test */
    public function it_returns_false_for_a_regular_user()
    {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        $user = new User;
        $user->email = 'regular@user.com';

        $this->assertFalse($this->superAdmin->is($user));
    }

    /** @test */
    public function it_returns_false_for_a_null_user()
    {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        $this->assertFalse($this->superAdmin->is(null));
    }

    /** @test */
    public function it_returns_false_if_the_super_admin_email_is_not_configured()
    {
        config(['singledb-tenancy.super_admin.email' => null]);

        $user = new User;
        $user->email = 'super@admin.com';

        $this->assertFalse($this->superAdmin->is($user));
    }
}
