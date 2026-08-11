<?php

namespace App\Filament\Resources\Descriptions;

use App\Filament\Resources\Descriptions\Pages\CreateDescription;
use App\Filament\Resources\Descriptions\Pages\EditDescription;
use App\Filament\Resources\Descriptions\Pages\ListDescriptions;
use App\Filament\Resources\Descriptions\Schemas\DescriptionForm;
use App\Filament\Resources\Descriptions\Tables\DescriptionsTable;
use App\Models\Description;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DescriptionResource extends Resource
{
    protected static ?string $model = Description::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static UnitEnum|string|null $navigationGroup = 'Подписки';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'origin_iata';

    public static function getNavigationLabel(): string
    {
        return 'Подписки пользователей';
    }

    public static function getModelLabel(): string
    {
        return 'подписка';
    }

    public static function getPluralModelLabel(): string
    {
        return 'подписки';
    }

    public static function form(Schema $schema): Schema
    {
        return DescriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DescriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDescriptions::route('/'),
            'create' => CreateDescription::route('/create'),
            'edit' => EditDescription::route('/{record}/edit'),
        ];
    }
}
