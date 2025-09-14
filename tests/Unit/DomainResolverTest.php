<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Resolvers\DomainResolver;

it('resolves tenant by exact domain match', function () {
    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $request = Request::create('https://example.test/dashboard');
    $resolver = app(DomainResolver::class);

    $resolved = $resolver->resolve($request);

    expect($resolved)->not()->toBeNull();
    expect($resolved->id)->toBe($tenant->id);
});

it('returns null when no domain matches', function () {
    $request = Request::create('https://nonexistent.test/dashboard');
    $resolver = app(DomainResolver::class);

    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('returns null when domain resolution is disabled', function () {
    config(['singledb-tenancy.resolution.domain.enabled' => false]);

    $tenant = Tenant::factory()->create([
        'domain' => 'example.test',
    ]);

    $request = Request::create('https://example.test/dashboard');
    $resolver = app(DomainResolver::class);

    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('handles requests without host gracefully', function () {
    $request = new Request;
    $resolver = app(DomainResolver::class);

    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});
