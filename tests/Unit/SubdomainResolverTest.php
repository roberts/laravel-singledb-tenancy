<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Resolvers\SubdomainResolver;

beforeEach(function () {
    config(['singledb-tenancy.resolution.subdomain.base_domain' => 'app.com']);
});

it('resolves tenant by subdomain', function () {
    $tenant = Tenant::factory()->create([
        'slug' => 'acme',
    ]);

    $request = Request::create('https://acme.app.com/dashboard');
    $resolver = app(SubdomainResolver::class);
    
    $resolved = $resolver->resolve($request);

    expect($resolved)->not()->toBeNull();
    expect($resolved->id)->toBe($tenant->id);
});

it('returns null for base domain without subdomain', function () {
    $request = Request::create('https://app.com/dashboard');
    $resolver = app(SubdomainResolver::class);
    
    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('returns null for reserved subdomains', function () {
    config(['singledb-tenancy.resolution.subdomain.reserved' => ['api', 'admin']]);

    $request = Request::create('https://api.app.com/v1/users');
    $resolver = app(SubdomainResolver::class);
    
    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('returns null for multi-level subdomains', function () {
    $request = Request::create('https://api.tenant.app.com/dashboard');
    $resolver = app(SubdomainResolver::class);
    
    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('returns null when subdomain resolution is disabled', function () {
    config(['singledb-tenancy.resolution.subdomain.enabled' => false]);

    $tenant = Tenant::factory()->create([
        'slug' => 'acme',
    ]);

    $request = Request::create('https://acme.app.com/dashboard');
    $resolver = app(SubdomainResolver::class);
    
    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('returns null when host does not match base domain', function () {
    $request = Request::create('https://acme.different.com/dashboard');
    $resolver = app(SubdomainResolver::class);
    
    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('returns null when no tenant matches subdomain', function () {
    $request = Request::create('https://nonexistent.app.com/dashboard');
    $resolver = app(SubdomainResolver::class);
    
    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('handles requests without host gracefully', function () {
    $request = new Request();
    $resolver = app(SubdomainResolver::class);
    
    $resolved = $resolver->resolve($request);

    expect($resolved)->toBeNull();
});

it('extracts correct subdomain from various hosts', function () {
    $tenant = Tenant::factory()->create(['slug' => 'test']);
    $resolver = app(SubdomainResolver::class);
    
    // Valid subdomain
    $request = Request::create('https://test.app.com/dashboard');
    expect($resolver->resolve($request))->not()->toBeNull();
    
    // Invalid - no subdomain
    $request = Request::create('https://app.com/dashboard');
    expect($resolver->resolve($request))->toBeNull();
    
    // Invalid - different domain
    $request = Request::create('https://test.other.com/dashboard');
    expect($resolver->resolve($request))->toBeNull();
});
