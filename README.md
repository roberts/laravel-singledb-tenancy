# Laravel Single Database Tenancy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/roberts/laravel-singledb-tenancy.svg?style=flat-square)](https://packagist.org/packages/roberts/laravel-singledb-tenancy)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/roberts/laravel-singledb-tenancy/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/roberts/laravel-singledb-tenancy/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/roberts/laravel-singledb-tenancy/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/roberts/laravel-singledb-tenancy/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/roberts/laravel-singledb-tenancy.svg?style=flat-square)](https://packagist.org/packages/roberts/laravel-singledb-tenancy)

A comprehensive Laravel package for implementing single-database multi-tenancy with automatic data isolation, tenant resolution, and flexible routing. This package provides a complete solution for SaaS applications that need to serve multiple tenants from a single database while maintaining strict data separation.

## Features

- **Automatic Tenant Resolution**: Resolve tenants by domain or subdomain
- **Data Isolation**: Automatic scoping of Eloquent models by tenant
- **Tenant Context Management**: Global tenant context with helper functions
- **Caching Support**: Optimized tenant resolution with configurable caching
- **Custom Routes**: Support for tenant-specific route files
- **Middleware Integration**: Easy integration with Laravel's middleware system
- **Development Tools**: Forced tenant mode for development and testing
- **Comprehensive Testing**: Full test suite with 64+ tests

## Requirements

- PHP 8.4+
- Laravel 12.0+

## Installation

Install the package via Composer:

```bash
composer require roberts/laravel-singledb-tenancy
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="laravel-singledb-tenancy-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-singledb-tenancy-config"
```

## Configuration

The package provides extensive configuration options in `config/singledb-tenancy.php`:

### Tenant Resolution Strategies

```php
'resolution' => [
    'strategies' => ['domain', 'subdomain'],
    'domain' => [
        'enabled' => true,
        'column' => 'domain',
    ],
    'subdomain' => [
        'enabled' => true,
        'column' => 'slug',
        'base_domain' => env('TENANT_BASE_DOMAIN', 'localhost'),
        'reserved' => ['api', 'admin', 'mail', 'www'],
    ],
],
```

### Caching Configuration

```php
'caching' => [
    'enabled' => env('TENANT_CACHE_ENABLED', true),
    'store' => env('TENANT_CACHE_STORE', 'array'),
    'ttl' => env('TENANT_CACHE_TTL', 3600),
],
```

### Error Handling

```php
'failure_handling' => [
    'unresolved_tenant' => 'continue', // continue|exception|redirect
    'suspended_tenant' => 'show_page', // show_page|redirect|block
    'suspended_view' => 'tenant.suspended',
],
```

## Basic Usage

### 1. Creating Tenants

```php
use Roberts\LaravelSingledbTenancy\Models\Tenant;

// Create a tenant with domain
$tenant = Tenant::create([
    'name' => 'Acme Corporation',
    'domain' => 'acme.com',
    'slug' => 'acme', // Auto-generated if not provided
]);

// Create a tenant for subdomain
$tenant = Tenant::create([
    'name' => 'Beta Company',
    'slug' => 'beta', // Will be accessible at beta.yourapp.com
]);
```

### 2. Making Models Tenant-Aware

Add the `HasTenant` trait to your models:

```php
use Roberts\LaravelSingledbTenancy\Traits\HasTenant;

class Post extends Model
{
    use HasTenant;
    
    protected $fillable = ['tenant_id'];
}
```

The trait automatically:
- Applies tenant scoping to all queries
- Sets the tenant_id when creating new models
- Provides a `tenant()` relationship

### 3. Setting Up Middleware

Apply the tenant resolution middleware to your routes:

```php
// All resolution strategies
Route::middleware(['web', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class);
});

// Domain resolution only
Route::middleware(['web', 'tenant:domain'])->group(function () {
    Route::get('/custom', CustomController::class);
});

// Subdomain resolution only
Route::middleware(['web', 'tenant:subdomain'])->group(function () {
    Route::get('/app/{page}', AppController::class);
});
```

## Advanced Features

### Tenant Context Management

The package provides global helper functions for tenant context:

```php
// Get current tenant
$tenant = current_tenant();
$tenantId = current_tenant_id();

// Check if tenant is set
if (has_tenant()) {
    // Tenant-specific logic
}

// Require tenant (throws exception if none set)
$tenant = require_tenant();

// Run code in specific tenant context
tenant_context()->runWith($tenant, function () {
    // This code runs with $tenant as current tenant
    $posts = Post::all(); // Only posts for $tenant
});
```

### Manual Tenant Context

You can manually set the tenant context:

```php
// Set tenant context
tenant_context()->set($tenant);

// Clear tenant context
tenant_context()->clear();

// Run without tenant context (see all data)
tenant_context()->runWithout(function () {
    $allPosts = Post::all(); // All posts across all tenants
});
```

### Model Scoping

Tenant-aware models are automatically scoped:

```php
// Automatically scoped to current tenant
$posts = Post::all();

// Query specific tenant
$posts = Post::forTenant($tenant)->get();

// Query all tenants (removes tenant scope)
$allPosts = Post::forAllTenants()->get();

// Custom tenant column (override default 'tenant_id')
class CustomModel extends Model
{
    use HasTenant;
    
    protected $tenantColumn = 'organization_id';
}
```

### Custom Route Files

The package supports tenant-specific route files. Create route files in `routes/tenants/`:

```
routes/
├── web.php              # Default routes for all tenants
└── tenants/
    ├── acme.php         # Custom routes for 'acme' tenant
    └── enterprise.php   # Custom routes for 'enterprise' tenant
```

Configure custom routing:

```php
'routing' => [
    'custom_routes_path' => base_path('routes/tenants'),
    'include_default_routes' => true,
    'route_file_naming' => 'slug', // slug|id|domain
],
```

### Development and Testing

Force a specific tenant during development:

```php
// .env
FORCE_TENANT_SLUG=dev-tenant

// All requests will resolve to the 'dev-tenant'
```

Disable tenant resolution for specific tests:

```php
public function test_admin_can_see_all_data()
{
    tenant_context()->runWithout(function () {
        // Test code that should see all tenant data
        $this->assertCount(10, Post::all());
    });
}
```

## Tenant Resolution

### Domain-Based Resolution

Matches the full request domain against the `domain` column:

```php
// example.com → Tenant with domain 'example.com'
// custom-domain.co.uk → Tenant with domain 'custom-domain.co.uk'
```

### Subdomain-Based Resolution

Extracts subdomain and matches against the `slug` column:

```php
// acme.yourapp.com → Tenant with slug 'acme'
// beta.yourapp.com → Tenant with slug 'beta'

// Reserved subdomains are ignored:
// api.yourapp.com → No tenant resolution
// www.yourapp.com → No tenant resolution
```

### Resolution Priority

When multiple strategies are enabled, resolution follows this order:

1. **Domain resolution** - Exact domain match
2. **Subdomain resolution** - Subdomain extraction and slug match

### Caching

Tenant resolution results are automatically cached to improve performance:

- Domain to tenant mapping
- Subdomain to tenant mapping  
- Custom route file existence

Cache is automatically invalidated when tenants are created, updated, or deleted.

## Error Handling

### Unresolved Tenant

When no tenant can be resolved from the request:

- `continue` - Continue without tenant context
- `exception` - Throw RuntimeException
- `redirect` - Redirect to specified route

### Suspended Tenant

When a tenant is suspended (soft deleted):

- `show_page` - Display suspension notice
- `block` - Return 403 Forbidden
- `redirect` - Redirect to specified route

## Testing

The package includes comprehensive tests covering all functionality:

```bash
# Run tests
composer test

# Run tests with coverage
composer test:coverage

# Run static analysis
composer analyse
```

Test your tenant-aware code:

```php
use Roberts\LaravelSingledbTenancy\Models\Tenant;

class PostTest extends TestCase
{
    public function test_posts_are_scoped_to_tenant()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        tenant_context()->set($tenant1);
        $post1 = Post::create(['title' => 'Tenant 1 Post']);
        
        tenant_context()->set($tenant2);
        $post2 = Post::create(['title' => 'Tenant 2 Post']);
        
        // Verify isolation
        tenant_context()->set($tenant1);
        $this->assertCount(1, Post::all());
                $this->assertEquals('Tenant 1 Post', Post::first()->title);
    }
}
```

## API Reference

### Models

#### Tenant

```php
// Properties
$tenant->id;           // Primary key
$tenant->name;         // Tenant display name
$tenant->slug;         // URL-safe identifier
$tenant->domain;       // Custom domain (optional)
$tenant->suspended_at; // Soft delete timestamp

// Methods
$tenant->isActive();                    // Check if tenant is active
$tenant->suspend();                     // Suspend tenant
$tenant->reactivate();                  // Reactivate suspended tenant
$tenant->url($path = '/');              // Generate tenant URL
Tenant::resolveByDomain($domain);       // Find tenant by domain
Tenant::resolveBySlug($slug);           // Find tenant by slug
```

### Services

#### TenantContext

```php
// Set/get current tenant
tenant_context()->set($tenant);
$tenant = tenant_context()->get();
tenant_context()->clear();

// Run code in tenant context
tenant_context()->runWith($tenant, $callback);
tenant_context()->runWithout($callback);

// Check tenant state
tenant_context()->has();
tenant_context()->id();
```

#### TenantCache

Automatic caching of tenant resolution - no direct usage required.

### Middleware

#### TenantResolutionMiddleware

```php
// Apply to routes
Route::middleware('tenant')->group(...);           // All strategies
Route::middleware('tenant:domain')->group(...);    // Domain only
Route::middleware('tenant:subdomain')->group(...); // Subdomain only
```

## Configuration Reference

### Full Configuration File

```php
<?php

return [
    // Tenant model configuration
    'tenant_model' => \Roberts\LaravelSingledbTenancy\Models\Tenant::class,
    
    // Resolution strategies
    'resolution' => [
        'strategies' => ['domain', 'subdomain'],
        
        'domain' => [
            'enabled' => true,
            'column' => 'domain',
        ],
        
        'subdomain' => [
            'enabled' => true,
            'column' => 'slug',
            'base_domain' => env('TENANT_BASE_DOMAIN', 'localhost'),
            'reserved' => ['api', 'admin', 'mail', 'www'],
        ],
    ],
    
    // Caching configuration
    'caching' => [
        'enabled' => env('TENANT_CACHE_ENABLED', true),
        'store' => env('TENANT_CACHE_STORE', 'array'),
        'ttl' => env('TENANT_CACHE_TTL', 3600),
    ],
    
    // Middleware behavior
    'middleware' => [
        'enabled' => true,
        'force_tenant_slug' => env('FORCE_TENANT_SLUG'),
    ],
    
    // Custom routing
    'routing' => [
        'custom_routes_path' => base_path('routes/tenants'),
        'include_default_routes' => true,
        'route_file_naming' => 'slug', // slug|id|domain
    ],
    
    // Error handling
    'failure_handling' => [
        'unresolved_tenant' => 'continue', // continue|exception|redirect
        'suspended_tenant' => 'show_page', // show_page|redirect|block
        'suspended_view' => 'tenant.suspended',
        'redirect_route' => 'tenant.select',
    ],
];
```

## Environment Variables

```bash
# Tenant caching
TENANT_CACHE_ENABLED=true
TENANT_CACHE_STORE=redis
TENANT_CACHE_TTL=3600

# Subdomain configuration
TENANT_BASE_DOMAIN=yourapp.com

# Development
FORCE_TENANT_SLUG=dev-tenant
```

## Migration

This package includes a tenant migration that creates the `tenants` table:

```php
Schema::create('tenants', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('domain')->nullable()->unique();
    $table->timestamps();
    $table->softDeletes('suspended_at');
});
```

Add `tenant_id` to your existing tables:

```php
Schema::table('posts', function (Blueprint $table) {
    $table->foreignId('tenant_id')->constrained();
});
```

## Best Practices

1. **Always use the HasTenant trait** on models that should be tenant-aware
2. **Cache tenant resolution** in production for better performance
3. **Test tenant isolation** thoroughly to prevent data leaks
4. **Use tenant context helpers** instead of manual database queries
5. **Configure reserved subdomains** to avoid conflicts with system routes
6. **Implement proper error handling** for unresolved or suspended tenants

## Troubleshooting

### Common Issues

**Tenant not resolving correctly**
- Verify middleware is applied to routes
- Check domain/subdomain configuration
- Ensure tenant exists in database

**Data appearing across tenants**
- Confirm HasTenant trait is applied to models
- Check tenant context is set properly
- Verify foreign key constraints

**Cache not working**
- Verify cache store configuration
- Check cache driver is properly configured
- Clear cache if stale data persists

**Custom routes not loading**
- Verify route file naming matches configuration
- Check custom routes path exists
- Ensure route files have proper PHP syntax

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Drew Roberts](https://github.com/drewroberts)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
