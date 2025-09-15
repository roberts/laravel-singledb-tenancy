<?php

use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\TenantResource;

describe('Filament Tenant Resource Configuration', function () {
    it('has auth.primary middleware configured', function () {
        $middleware = TenantResource::getMiddleware();
        
        expect($middleware)->toBeArray();
        expect($middleware)->toContain('auth.primary');
    });

    it('resource is protected from unauthorized access', function () {
        $resource = new TenantResource();
        
        // Verify the resource class extends the correct base class
        expect($resource)->toBeInstanceOf(\Filament\Resources\Resource::class);
        
        // Verify middleware is properly configured
        expect(TenantResource::getMiddleware())->not->toBeEmpty();
    });
});
