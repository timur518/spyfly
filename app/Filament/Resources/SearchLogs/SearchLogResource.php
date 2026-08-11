<?php

namespace App\Filament\Resources\SearchLogs;

use App\Filament\Resources\SearchLogs\Pages\CreateSearchLog;
use App\Filament\Resources\SearchLogs\Pages\EditSearchLog;
use App\Filament\Resources\SearchLogs\Pages\ListSearchLogs;
use App\Filament\Resources\SearchLogs\Schemas\SearchLogForm;
use App\Filament\Resources\SearchLogs\Tables\SearchLogsTable;
use App\Models\SearchLog;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SearchLogResource extends Resource
{
    protected static ?string $model = SearchLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static UnitEnum|string|null $navigationGroup = 'Рейсы';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'origin_iata';

    public static function getNavigationLabel(): string
    {
        return 'История поисков';
    }

    public static function getModelLabel(): string
    {
        return 'поиск';
    }

    public static function getPluralModelLabel(): string
    {
        return 'история поисков';
    }

    public static function form(Schema $schema): Schema
    {
        return SearchLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SearchLogsTable::configure($table);
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
            'index' => ListSearchLogs::route('/'),
            'create' => CreateSearchLog::route('/create'),
            'edit' => EditSearchLog::route('/{record}/edit'),
        ];
    }
}
