<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Resolvers\DomainResolver;

beforeEach(function () {
    $this->resolver = app(DomainResolver::class);
});

describe('Domain Resolver', function () {
    describe('Tenant Resolution', function () {
        it('resolves tenant by exact domain match', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);
            $request = Request::create('https://example.test/dashboard');

            $resolved = $this->resolver->resolve($request);

            expect($resolved)->not()->toBeNull()->and($resolved->id)->toBe($tenant->id);
        });

        it('returns null when no domain matches', function () {
            $request = Request::create('https://nonexistent.test/dashboard');

            expect($this->resolver->resolve($request))->toBeNull();
        });

        it('handles requests without host gracefully', function () {
            $request = new Request;

            expect($this->resolver->resolve($request))->toBeNull();
        });
    });
});
