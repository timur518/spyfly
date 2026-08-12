<?php

namespace App\Filament\Resources\Descriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class DescriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable(),
                TextColumn::make('route_summary')
                    ->label('Маршрут')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state))
                    ->html(),
                TextColumn::make('date_range_summary')
                    ->label('Даты вылета')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state))
                    ->html(),
                TextColumn::make('price_summary')
                    ->label('Цена до')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state))
                    ->html(),
                TextColumn::make('stay_summary')
                    ->label('Срок')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state))
                    ->html(),
                TextColumn::make('channel_summary')
                    ->label('Канал')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::multiline($state))
                    ->html(),
                TextColumn::make('matched_flights')
                    ->label('Рейсы')
                    ->badge()
                    ->formatStateUsing(fn (?array $state): string => $state ? count($state) . ' рейсов' : '0 рейсов')
                    ->color(fn (?array $state): string => $state ? 'info' : 'gray'),
                TextColumn::make('is_active')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Активна' : 'Пауза')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('last_notified_at')
                    ->label('Последнее уведомление')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создана')
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
            ->emptyStateHeading('Подписок пока нет')
            ->emptyStateDescription('Создайте первую подписку, чтобы отслеживать нужные маршруты.')
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
}
