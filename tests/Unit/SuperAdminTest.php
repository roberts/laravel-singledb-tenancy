<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Roberts\LaravelSingledbTenancy\Services\SuperAdmin;

beforeEach(function () {
    $this->superAdmin = app(SuperAdmin::class);
});

describe('SuperAdmin Service', function () {
    it('returns true for the super admin user', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);
        $superAdmin = app(SuperAdmin::class); // Get fresh instance

        $user = new User;
        $user->email = 'super@admin.com';

        expect($superAdmin->is($user))->toBeTrue();
    });

    it('returns false for a regular user', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);
        $superAdmin = app(SuperAdmin::class); // Get fresh instance

        $user = new User;
        $user->email = 'regular@user.com';

        expect($superAdmin->is($user))->toBeFalse();
    });

    it('returns false for a null user', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);
        $superAdmin = app(SuperAdmin::class); // Get fresh instance

        expect($superAdmin->is(null))->toBeFalse();
    });

    it('returns false if the super admin email is not configured', function () {
        config(['singledb-tenancy.super_admin.email' => null]);
        $superAdmin = app(SuperAdmin::class); // Get fresh instance

        $user = new User;
        $user->email = 'super@admin.com';

        expect($superAdmin->is($user))->toBeFalse();
    });

    it('returns false if the super admin email is empty string', function () {
        config(['singledb-tenancy.super_admin.email' => '']);
        $superAdmin = app(SuperAdmin::class); // Get fresh instance

        $user = new User;
        $user->email = 'super@admin.com';

        expect($superAdmin->is($user))->toBeFalse();
    });

    it('handles case sensitivity correctly', function () {
        config(['singledb-tenancy.super_admin.email' => 'SUPER@ADMIN.COM']);
        $superAdmin = app(SuperAdmin::class); // Get fresh instance

        $user = new User;
        $user->email = 'super@admin.com';

        expect($superAdmin->is($user))->toBeFalse();
    });

    it('works with exact email match', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);
        $superAdmin = app(SuperAdmin::class); // Get fresh instance

        $user1 = new User;
        $user1->email = 'super@admin.com';

        $user2 = new User;
        $user2->email = 'super@admin.co'; // Missing 'm'

        expect($superAdmin->is($user1))->toBeTrue();
        expect($superAdmin->is($user2))->toBeFalse();
    });

    it('handles user objects without email attribute gracefully', function () {
        config(['singledb-tenancy.super_admin.email' => 'super@admin.com']);
        $superAdmin = app(SuperAdmin::class); // Get fresh instance

        $user = new User;
        // Not setting email attribute

        expect($superAdmin->is($user))->toBeFalse();
    });
});
