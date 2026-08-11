<?php

namespace App\Filament\Resources\SearchLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class SearchLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('searched_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable(),
                TextColumn::make('route_summary')
                    ->label('Маршрут')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state, ' · '))
                    ->html()
                    ->searchable(),
                TextColumn::make('date_range_summary')
                    ->label('Даты')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state, ' — '))
                    ->html()
                    ->searchable(),
                TextColumn::make('price_summary')
                    ->label('Цены')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state, ' · '))
                    ->html(),
                TextColumn::make('results_coverage_summary')
                    ->label('Результаты')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state, ' · '))
                    ->html(),
                TextColumn::make('search_status_summary')
                    ->label('Дата и статус')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state, ' · '))
                    ->html(),
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
            ->emptyStateHeading('История поисков пока пуста')
            ->emptyStateDescription('После первых поисков здесь появятся все запросы и их результаты.')
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function multiline(?string $state, string $delimiter): HtmlString
    {
        if ($state === null || $state === '') {
            return new HtmlString('—');
        }

        $parts = array_map(
            static fn (string $part): string => e(trim($part)),
            explode($delimiter, $state),
        );

        return new HtmlString(implode('<br>', $parts));
    }
}
