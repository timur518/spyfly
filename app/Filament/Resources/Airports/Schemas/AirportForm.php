<?php

namespace App\Filament\Resources\Airports\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AirportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('city')
                    ->label('Город')
                    ->required(),
                TextInput::make('name')
                    ->label('Название аэропорта')
                    ->required(),
                TextInput::make('iata_code')
                    ->label('Код IATA')
                    ->required(),
                TextInput::make('additional_names')
                    ->label('Дополнительные названия')
                    ->helperText('Поисковые слова для поиска')
                    ->columnSpanFull(),
                Checkbox::make('is_popular_destination')
                    ->label('Популярное направление'),
                Checkbox::make('is_active')
                    ->label('Активен'),
            ]);
    }
}
