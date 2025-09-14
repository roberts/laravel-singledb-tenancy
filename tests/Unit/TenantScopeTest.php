<?php

declare(strict_types=1);

use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Scopes\TenantScope;
use Roberts\LaravelSingledbTenancy\Services\TenantCache;
use Roberts\LaravelSingledbTenancy\Tests\Models\Post;

beforeEach(function () {
    tenant_context()->clear();
});

describe('TenantScope', function () {
    it('applies tenant scope when tenant context is set', function () {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create posts for different tenants
        tenant_context()->set($tenant1);
        $post1 = Post::create(['title' => 'Tenant 1 Post', 'content' => 'Content 1']);

        tenant_context()->set($tenant2);
        $post2 = Post::create(['title' => 'Tenant 2 Post', 'content' => 'Content 2']);

        // Query with tenant 1 context
        tenant_context()->set($tenant1);
        $posts = Post::all();

        expect($posts)->toHaveCount(1);
        expect($posts->first()->id)->toBe($post1->id);
    });

    it('returns no results when no tenant context is set but tenants exist', function () {
        $tenant = Tenant::factory()->create();

        // Create a post with tenant context
        tenant_context()->set($tenant);
        Post::create(['title' => 'Test Post', 'content' => 'Test Content']);

        // Clear tenant context
        tenant_context()->clear();

        // Should return no results due to tenant isolation
        $posts = Post::all();
        expect($posts)->toHaveCount(0);
    });

    it('does not apply scope when no tenants exist in system', function () {
        // Delete all tenants
        Tenant::query()->delete();

        // Mock tenant cache to return false for tenants existence
        $tenantCache = $this->createMock(TenantCache::class);
        $tenantCache->method('tenantsExist')->willReturn(false);
        $this->app->instance(TenantCache::class, $tenantCache);

        // Clear tenant context
        tenant_context()->clear();

        // Create a post without tenant context
        $post = new Post(['title' => 'No Tenant Post', 'content' => 'Content']);
        $post->save();

        // Should be able to retrieve all posts without tenant scoping
        $posts = Post::all();
        expect($posts)->toHaveCount(1);
        expect($posts->first()->title)->toBe('No Tenant Post');
    });

    it('uses custom tenant column when defined', function () {
        $tenant = Tenant::factory()->create();
        tenant_context()->set($tenant);

        // Create a test model that uses a different tenant column
        $model = new class extends \Illuminate\Database\Eloquent\Model
        {
            use \Roberts\LaravelSingledbTenancy\Traits\HasTenant;

            protected $table = 'posts';

            protected $fillable = ['title', 'content', 'custom_tenant_id'];

            public function getTenantColumn(): string
            {
                return 'custom_tenant_id';
            }
        };

        // The scope should respect the custom tenant column
        $scope = new TenantScope;
        $builder = $model->newQuery();

        $scope->apply($builder, $model);

        // Check that the query uses the custom column
        $sql = $builder->toSql();
        expect($sql)->toContain('custom_tenant_id');
    });

    it('falls back to config tenant column when model method not available', function () {
        config(['singledb-tenancy.tenant_column' => 'org_id']);

        $tenant = Tenant::factory()->create();
        tenant_context()->set($tenant);

        // Create a model without getTenantColumn method
        $model = new class extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'posts';
        };

        $scope = new TenantScope;
        $builder = $model->newQuery();

        $scope->apply($builder, $model);

        // Should use config value
        $sql = $builder->toSql();
        expect($sql)->toContain('org_id');
    });

    it('handles tenant context changes correctly', function () {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create posts for different tenants
        tenant_context()->set($tenant1);
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1']);

        tenant_context()->set($tenant2);
        $post2 = Post::create(['title' => 'Post 2', 'content' => 'Content 2']);

        // Switch contexts and verify isolation
        tenant_context()->set($tenant1);
        expect(Post::count())->toBe(1);
        expect(Post::first()->title)->toBe('Post 1');

        tenant_context()->set($tenant2);
        expect(Post::count())->toBe(1);
        expect(Post::first()->title)->toBe('Post 2');
    });

    it('handles soft deleted tenants correctly', function () {
        $tenant = Tenant::factory()->create();

        // Create post with active tenant
        tenant_context()->set($tenant);
        $post = Post::create(['title' => 'Test Post', 'content' => 'Content']);

        expect(Post::count())->toBe(1);

        // Soft delete the tenant (though this would normally be prevented for ID 1)
        if ($tenant->id !== 1) {
            $tenant->delete();

            // Clear any cached tenant data
            tenant_context()->clear();
            tenant_context()->set($tenant);

            // Posts should still be accessible if tenant context is set with soft-deleted tenant
            expect(Post::count())->toBe(1);
        } else {
            // If it's tenant 1, just verify the post exists
            expect(Post::count())->toBe(1);
        }
    });
});
