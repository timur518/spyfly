<?php

namespace App\Filament\Resources\Alerts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AlertForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('search_log_id')
                    ->label('Поиск')
                    ->relationship('searchLog', 'id')
                    ->searchable()
                    ->preload(),
                TextInput::make('origin_iata')
                    ->label('Откуда')
                    ->required(),
                TextInput::make('destination_iata')
                    ->label('Куда')
                    ->required(),
                DatePicker::make('departure_date')
                    ->label('Дата вылета')
                    ->required(),
                DatePicker::make('return_date')
                    ->label('Дата возврата'),
                TextInput::make('price')
                    ->label('Цена')
                    ->required()
                    ->numeric()
                    ->prefix('₽'),
                TextInput::make('baseline_price')
                    ->label('Средняя цена')
                    ->numeric()
                    ->prefix('₽'),
                TextInput::make('deviation_percent')
                    ->label('Отклонение, %')
                    ->numeric(),
                TextInput::make('score')
                    ->label('Баллы')
                    ->numeric(),
            ]);
    }

}
