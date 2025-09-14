<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Tests\Models\Post;

beforeEach(function () {
    // Create the posts table for our test model if it doesn't exist
    if (!Schema::hasTable('posts')) {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }
});

afterEach(function () {
    Schema::dropIfExists('posts');
});

describe('HasTenant Trait', function () {
    describe('Automatic Tenant Scoping', function () {
        it('automatically applies tenant scope to queries', function () {
            [$tenant1, $tenant2] = Tenant::factory(2)->create();

            // Create posts for different tenants
            tenant_context()->set($tenant1);
            $post1 = Post::create(['title' => 'Tenant 1 Post', 'content' => 'Content 1']);

            tenant_context()->set($tenant2);
            $post2 = Post::create(['title' => 'Tenant 2 Post', 'content' => 'Content 2']);

            // Verify tenant isolation
            tenant_context()->set($tenant1);
            expect(Post::count())->toBe(1)
                ->and(Post::first()->title)->toBe('Tenant 1 Post');

            tenant_context()->set($tenant2);
            expect(Post::count())->toBe(1)
                ->and(Post::first()->title)->toBe('Tenant 2 Post');
        });

        it('handles queries without tenant context gracefully', function () {
            $tenant = Tenant::factory()->create();
            tenant_context()->set($tenant);

            Post::create(['title' => 'Test Post', 'content' => 'Test Content']);
            tenant_context()->clear();

            expect(Post::count())->toBe(0)
                ->and(Post::forAllTenants()->count())->toBe(1);
        });
    });

    describe('Automatic Tenant Assignment', function () {
        it('automatically assigns tenant_id when creating models', function () {
            $tenant = Tenant::factory()->create();
            tenant_context()->set($tenant);

            $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);

            expect($post)
                ->tenant_id->toBe($tenant->id)
                ->tenant->id->toBe($tenant->id);
        });

        it('does not automatically assign tenant_id if already set', function () {
            [$tenant1, $tenant2] = Tenant::factory(2)->create();
            tenant_context()->set($tenant1);

            $post = Post::create([
                'title' => 'Test Post',
                'content' => 'Test Content',
                'tenant_id' => $tenant2->id,
            ]);

            expect($post->tenant_id)->toBe($tenant2->id);
        });
    });

    describe('Query Scopes', function () {
        beforeEach(function () {
            [$this->tenant1, $this->tenant2] = Tenant::factory(2)->create();

            tenant_context()->set($this->tenant1);
            Post::create(['title' => 'Tenant 1 Post', 'content' => 'Content 1']);

            tenant_context()->set($this->tenant2);
            Post::create(['title' => 'Tenant 2 Post', 'content' => 'Content 2']);

            tenant_context()->clear();
        });

        it('allows querying for all tenants when using forAllTenants scope', function () {
            expect(Post::forAllTenants()->count())->toBe(2);
        });

        it('allows querying for specific tenant using forTenant scope', function () {
            $tenant1Posts = Post::forTenant($this->tenant1)->get();
            expect($tenant1Posts)->toHaveCount(1)
                ->and($tenant1Posts->first()->title)->toBe('Tenant 1 Post');

            $tenant2Posts = Post::forTenant($this->tenant2->id)->get();
            expect($tenant2Posts)->toHaveCount(1)
                ->and($tenant2Posts->first()->title)->toBe('Tenant 2 Post');
        });
    });

    describe('Relationships and Metadata', function () {
        it('provides tenant relationship', function () {
            $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
            tenant_context()->set($tenant);

            $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);

            expect($post->tenant)
                ->toBeInstanceOf(Tenant::class)
                ->name->toBe('Test Tenant');
        });

        it('returns correct tenant column name', function () {
            $post = new Post;

            expect($post)
                ->getTenantColumn()->toBe('tenant_id')
                ->getQualifiedTenantColumn()->toBe('posts.tenant_id');
        });
    });
});
