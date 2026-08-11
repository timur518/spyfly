<?php

namespace App\Filament\Resources\Airports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AirportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Название аэропорта')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('iata_code')
                    ->label('Код IATA')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_popular_destination')
                    ->label('Популярное направление')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Аэропорты ещё не добавлены')
            ->emptyStateDescription('Добавьте первый аэропорт, чтобы он появился в списке.')
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
