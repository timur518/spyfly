<?php

namespace App\Filament\Resources\Alerts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('searchLog.id')
                    ->label('Поиск')
                    ->searchable(),
                TextColumn::make('score')
                    ->label('Оценка')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state === null ? '—' : $state . ' баллов')
                    ->color(fn ($state): string => self::scoreColor($state))
                    ->sortable(),
                TextColumn::make('route_summary')
                    ->label('Маршрут')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state))
                    ->html(),
                TextColumn::make('date_summary')
                    ->label('Даты')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state))
                    ->html(),
                TextColumn::make('price_summary')
                    ->label('Цены')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state))
                    ->html(),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Сигналы рейсов пока не найдены')
            ->emptyStateDescription('Когда появится интересная цена или сработает правило, запись появится здесь.')
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function multiline(?string $state): HtmlString
    {
        if ($state === null || $state === '') {
            return new HtmlString('—');
        }

        $parts = array_map(
            static fn (string $part): string => e(trim($part)),
            preg_split('/\r\n|\r|\n/', $state) ?: [],
        );

        return new HtmlString(implode('<br>', $parts));
    }

    private static function scoreColor(mixed $state): string
    {
        $score = (int) ($state ?? 0);

        return match (true) {
            $score >= 80 => 'success',
            $score >= 60 => 'warning',
            $score >= 40 => 'info',
            default => 'danger',
        };
    }

}
