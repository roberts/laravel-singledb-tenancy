<?php

namespace Roberts\LaravelSingledbTenancy\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, $set) {
                        if ($operation !== 'create') {
                            return;
                        }

                        $set('slug', \Illuminate\Support\Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['regex:/^[a-z0-9-]+$/'])
                    ->helperText('Used for subdomain and routing. Only lowercase letters, numbers, and hyphens allowed.'),

                TextInput::make('domain')
                    ->label('Domain')
                    ->maxLength(255)
                    ->url()
                    ->helperText('Full domain URL for this tenant (optional).'),

                TextEntry::make('created_at')
                    ->label('Created at')
                    ->state(fn ($record): string => $record?->created_at?->diffForHumans() ?? '-')
                    ->visibleOn('edit'),

                TextEntry::make('updated_at')
                    ->label('Last modified at')
                    ->state(fn ($record): string => $record?->updated_at?->diffForHumans() ?? '-')
                    ->visibleOn('edit'),
            ]);
    }
}
