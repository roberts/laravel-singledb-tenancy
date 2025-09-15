<?php

use Roberts\LaravelSingledbTenancy\Models\Tenant;

it('uses slug as route key', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);

    expect($tenant->getRouteKeyName())->toBe('slug');
    expect($tenant->getRouteKey())->toBe('test-tenant');
});

it('can resolve tenant by slug for route binding', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);

    $resolved = Tenant::resolveRouteBinding('test-tenant');

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($tenant->id);
    expect($resolved->slug)->toBe('test-tenant');
});
