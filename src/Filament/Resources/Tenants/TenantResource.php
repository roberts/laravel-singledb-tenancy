<?php

namespace Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Pages\CreateTenant;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Pages\EditTenant;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Pages\ListTenants;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Schemas\TenantForm;
use Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Tables\TenantsTable;
use Roberts\LaravelSingledbTenancy\Models\Tenant;

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
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
