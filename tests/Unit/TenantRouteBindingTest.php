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
    
    // Test that we can find the tenant by its slug
    $resolved = Tenant::where('slug', 'test-tenant')->first();
    
    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($tenant->id);
    expect($resolved->slug)->toBe('test-tenant');
});