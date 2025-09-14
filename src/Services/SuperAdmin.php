<?php

declare(strict_types=1);

namespace Roberts\LaravelSingledbTenancy\Services;

use Illuminate\Contracts\Auth\Authenticatable;

class SuperAdmin
{
    public function is(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        $superAdminEmail = config('singledb-tenancy.super_admin.email');

        if (! $superAdminEmail) {
            return false;
        }

        // Check if user has an email attribute/property
        if (! isset($user->email) && ! property_exists($user, 'email')) {
            return false;
        }

        return $user->email === $superAdminEmail;
    }
}
