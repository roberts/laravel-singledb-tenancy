<?php

use Roberts\LaravelSingledbTenancy\Models\Tenant;

describe('Tenant Model', function () {
    describe('Creation and Basic Operations', function () {
        it('can create a tenant with auto-generated slug', function () {
            $tenant = Tenant::factory()->make([
                'name' => 'Test Company',
                'domain' => 'domain.test',
                'slug' => null,
            ]);
            $tenant->save();

            expect($tenant)
                ->name->toBe('Test Company')
                ->domain->toBe('domain.test')
                ->slug->toBe('test-company')
                ->isActive()->toBeTrue();
        });

        it('auto generates slug from name', function () {
            $tenant = Tenant::factory()->create([
                'name' => 'My Test Company',
                'slug' => null,
            ]);

            expect($tenant->slug)->toBe('my-test-company');
        });
    });

    describe('Domain Resolution', function () {
        it('can resolve tenant by domain', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);
            $resolved = Tenant::resolveByDomain('example.test');

            expect($resolved)
                ->not->toBeNull()
                ->id->toBe($tenant->id);
        });

        it('does not resolve suspended tenants', function () {
            $tenant = Tenant::factory()->create([
                'id' => 2,
                'domain' => 'suspended.test',
            ]);
            $tenant->suspend();

            expect(Tenant::resolveByDomain('suspended.test'))->toBeNull();
        });
    });

    describe('Tenant Lifecycle', function () {
        it('can suspend and reactivate tenant', function () {
            $tenant = Tenant::factory()->create(['id' => 2]);

            expect($tenant->isActive())->toBeTrue();

            $tenant->suspend();
            $tenant->refresh();
            expect($tenant->isActive())->toBeFalse();

            $tenant->reactivate();
            $tenant->refresh();
            expect($tenant->isActive())->toBeTrue();
        });
    });

    describe('URL Generation', function () {
        it('generates correct urls', function () {
            $tenant = Tenant::factory()->create(['domain' => 'example.test']);
            request()->server->set('HTTPS', 'on');

            expect($tenant)
                ->url()->toBe('https://example.test')
                ->url('dashboard')->toBe('https://example.test/dashboard')
                ->url('/admin/users')->toBe('https://example.test/admin/users');
        });
    });
});
