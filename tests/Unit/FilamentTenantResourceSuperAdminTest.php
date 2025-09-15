<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\TenantResource;
use Roberts\LaravelSingledbTenancy\Services\SuperAdmin;

describe('Filament Tenant Resource SuperAdmin Authorization', function () {
    beforeEach(function () {
        // Clear any existing auth state
        Auth::logout();
    });

    it('allows access when user is super admin', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        $superAdminUser = new User;
        $superAdminUser->email = 'super@admin.com';
        $superAdminUser->id = 1;

        Auth::login($superAdminUser);

        expect(TenantResource::canAccessResource())->toBeTrue();
        expect(TenantResource::canAccess())->toBeTrue();
        expect(TenantResource::shouldRegisterNavigation())->toBeTrue();
    });

    it('denies access when user is not super admin', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        $regularUser = new User;
        $regularUser->email = 'regular@user.com';
        $regularUser->id = 2;

        Auth::login($regularUser);

        expect(TenantResource::canAccessResource())->toBeFalse();
        expect(TenantResource::canAccess())->toBeFalse();
        expect(TenantResource::shouldRegisterNavigation())->toBeFalse();
    });

    it('denies access when no user is authenticated', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        // Ensure no user is logged in
        Auth::logout();
        expect(Auth::user())->toBeNull();

        expect(TenantResource::canAccessResource())->toBeFalse();
        expect(TenantResource::canAccess())->toBeFalse();
        expect(TenantResource::shouldRegisterNavigation())->toBeFalse();
    });

    it('denies access when super admin email is not configured', function () {
        config(['singledb-tenancy.super_admin.email' => null]);

        $user = new User;
        $user->email = 'any@user.com';
        $user->id = 1;

        Auth::login($user);

        expect(TenantResource::canAccessResource())->toBeFalse();
        expect(TenantResource::canAccess())->toBeFalse();
        expect(TenantResource::shouldRegisterNavigation())->toBeFalse();
    });

    it('denies access when super admin email is empty string', function () {
        config(['singledb-tenancy.super_admin.email' => '']);

        $user = new User;
        $user->email = 'any@user.com';
        $user->id = 1;

        Auth::login($user);

        expect(TenantResource::canAccessResource())->toBeFalse();
        expect(TenantResource::canAccess())->toBeFalse();
        expect(TenantResource::shouldRegisterNavigation())->toBeFalse();
    });

    it('handles case sensitivity correctly', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        $user = new User;
        $user->email = 'SUPER@ADMIN.COM'; // Different case
        $user->id = 1;

        Auth::login($user);

        expect(TenantResource::canAccessResource())->toBeFalse();
        expect(TenantResource::canAccess())->toBeFalse();
        expect(TenantResource::shouldRegisterNavigation())->toBeFalse();
    });

    it('works with SuperAdmin service integration', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        $superAdminService = app(SuperAdmin::class);

        $superAdminUser = new User;
        $superAdminUser->email = 'super@admin.com';

        $regularUser = new User;
        $regularUser->email = 'regular@user.com';

        // Test SuperAdmin service directly
        expect($superAdminService->is($superAdminUser))->toBeTrue();
        expect($superAdminService->is($regularUser))->toBeFalse();

        // Test with authentication
        Auth::login($superAdminUser);
        expect(TenantResource::canAccessResource())->toBeTrue();

        Auth::login($regularUser);
        expect(TenantResource::canAccessResource())->toBeFalse();
    });

    it('prevents navigation registration for non-super admin users', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);

        // Test with super admin - navigation should be registered
        $superAdminUser = new User;
        $superAdminUser->email = 'super@admin.com';
        Auth::login($superAdminUser);

        expect(TenantResource::shouldRegisterNavigation())->toBeTrue();

        // Test with regular user - navigation should not be registered
        $regularUser = new User;
        $regularUser->email = 'regular@user.com';
        Auth::login($regularUser);

        expect(TenantResource::shouldRegisterNavigation())->toBeFalse();
    });
});
