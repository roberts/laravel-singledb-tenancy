<?php

use Roberts\LaravelSingledbTenancy\Models\Tenant;

it('can create a tenant', function () {
    $tenant = Tenant::factory()->make([
        'name' => 'Test Company',
        'domain' => 'domain.test',
    ]);

    // Don't set slug, let it auto-generate
    $tenant->slug = null;
    $tenant->save();

    expect($tenant->name)->toBe('Test Company')
        ->and($tenant->domain)->toBe('domain.test')
        ->and($tenant->slug)->toBe('test-company')
        ->and($tenant->isActive())->toBeTrue();
});

it('can resolve tenant by domain', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $resolved = Tenant::resolveByDomain('example.test');

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($tenant->id);
});

it('can suspend and reactivate tenant', function () {
    $tenant = Tenant::factory()->create(['id' => 2]); // Use ID 2 to avoid deletion protection

    expect($tenant->isActive())->toBeTrue();

    // Suspend tenant
    $tenant->suspend();
    $tenant->refresh();

    expect($tenant->isActive())->toBeFalse();

    // Reactivate tenant
    $tenant->reactivate();
    $tenant->refresh();

    expect($tenant->isActive())->toBeTrue();
});

it('does not resolve suspended tenants', function () {
    $tenant = Tenant::factory()->create([
        'id' => 2, // Use ID 2 to avoid deletion protection
        'domain' => 'suspended.test',
    ]);

    $tenant->suspend();

    $resolved = Tenant::resolveByDomain('suspended.test');

    expect($resolved)->toBeNull();
});

it('auto generates slug from name', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'My Test Company',
        'slug' => null,
    ]);

    expect($tenant->slug)->toBe('my-test-company');
});

it('generates correct urls', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    request()->server->set('HTTPS', 'on');

    expect($tenant->url())->toBe('https://example.test')
        ->and($tenant->url('dashboard'))->toBe('https://example.test/dashboard')
        ->and($tenant->url('/admin/users'))->toBe('https://example.test/admin/users');
});
