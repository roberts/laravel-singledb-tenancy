<?php

namespace Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Pages\CreateTenant;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Pages\EditTenant;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Pages\ListTenants;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Schemas\TenantForm;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Tables\TenantsTable;
use Roberts\LaravelSingledbTenancy\Models\Tenant;
use Roberts\LaravelSingledbTenancy\Services\SuperAdmin;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Super Admin';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $pluralLabel = 'Tenants';

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record:slug}/edit'),
        ];
    }

    public static function getMiddleware(): array
    {
        return [
            'auth.primary',
        ];
    }

    public static function canAccess(): bool
    {
        return static::canAccessResource();
    }

    public static function canAccessResource(): bool
    {
        $user = Auth::user();
        
        if (! $user) {
            return false;
        }

        return app(SuperAdmin::class)->is($user);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccessResource();
    }
}
