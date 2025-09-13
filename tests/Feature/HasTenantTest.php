<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Tests\Models\Post;

beforeEach(function () {
    // Create the posts table for our test model
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained('tenants');
        $table->string('title');
        $table->text('content');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('posts');
});

it('automatically applies tenant scope to queries', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    // Create posts for different tenants
    tenant_context()->set($tenant1);
    $post1 = Post::create(['title' => 'Tenant 1 Post', 'content' => 'Content 1']);

    tenant_context()->set($tenant2);
    $post2 = Post::create(['title' => 'Tenant 2 Post', 'content' => 'Content 2']);

    // When querying with tenant 1 context, only see tenant 1 posts
    tenant_context()->set($tenant1);
    expect(Post::count())->toBe(1);
    expect(Post::first()->title)->toBe('Tenant 1 Post');

    // When querying with tenant 2 context, only see tenant 2 posts
    tenant_context()->set($tenant2);
    expect(Post::count())->toBe(1);
    expect(Post::first()->title)->toBe('Tenant 2 Post');
});

it('automatically assigns tenant_id when creating models', function () {
    $tenant = Tenant::factory()->create();
    tenant_context()->set($tenant);

    $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);

    expect($post->tenant_id)->toBe($tenant->id);
    expect($post->tenant->id)->toBe($tenant->id);
});

it('allows querying for all tenants when using forAllTenants scope', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    // Create posts for different tenants
    tenant_context()->set($tenant1);
    Post::create(['title' => 'Tenant 1 Post', 'content' => 'Content 1']);

    tenant_context()->set($tenant2);
    Post::create(['title' => 'Tenant 2 Post', 'content' => 'Content 2']);

    // Clear tenant context
    tenant_context()->clear();

    // Should see all posts when using forAllTenants scope
    expect(Post::forAllTenants()->count())->toBe(2);
});

it('allows querying for specific tenant using forTenant scope', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    // Create posts for different tenants
    tenant_context()->set($tenant1);
    Post::create(['title' => 'Tenant 1 Post', 'content' => 'Content 1']);

    tenant_context()->set($tenant2);
    Post::create(['title' => 'Tenant 2 Post', 'content' => 'Content 2']);

    // Clear tenant context
    tenant_context()->clear();

    // Should see only tenant 1 posts when using forTenant scope
    $tenant1Posts = Post::forTenant($tenant1)->get();
    expect($tenant1Posts)->toHaveCount(1);
    expect($tenant1Posts->first()->title)->toBe('Tenant 1 Post');

    // Should see only tenant 2 posts when using forTenant scope with ID
    $tenant2Posts = Post::forTenant($tenant2->id)->get();
    expect($tenant2Posts)->toHaveCount(1);
    expect($tenant2Posts->first()->title)->toBe('Tenant 2 Post');
});

it('provides tenant relationship', function () {
    $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
    tenant_context()->set($tenant);

    $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);

    expect($post->tenant)->toBeInstanceOf(Tenant::class);
    expect($post->tenant->name)->toBe('Test Tenant');
});

it('returns correct tenant column name', function () {
    $post = new Post;

    expect($post->getTenantColumn())->toBe('tenant_id');
    expect($post->getQualifiedTenantColumn())->toBe('posts.tenant_id');
});

it('does not automatically assign tenant_id if already set', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    tenant_context()->set($tenant1);

    // Explicitly set a different tenant_id
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'tenant_id' => $tenant2->id,
    ]);

    // Should keep the explicitly set tenant_id
    expect($post->tenant_id)->toBe($tenant2->id);
});

it('allows custom tenant column name', function () {
    // Create a custom test model with different tenant column
    $customModel = new class extends Post
    {
        protected $tenantColumn = 'organization_id';
    };

    expect($customModel->getTenantColumn())->toBe('organization_id');
});

it('handles queries without tenant context gracefully', function () {
    $tenant = Tenant::factory()->create();
    tenant_context()->set($tenant);

    // Create a post with tenant context
    Post::create(['title' => 'Test Post', 'content' => 'Test Content']);

    // Clear tenant context
    tenant_context()->clear();

    // Should return 0 results when no tenant context is set
    expect(Post::count())->toBe(0);

    // But should see all posts when using forAllTenants
    expect(Post::forAllTenants()->count())->toBe(1);
});
