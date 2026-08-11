<?php

namespace App\Filament\Resources\SearchLogs\Schemas;

use App\Models\Airport;
use JsonException;
use InvalidArgumentException;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SearchLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Маршрут')
                    ->schema([
                        Select::make('origin_iata')
                            ->label('Откуда')
                            ->options(self::airportOptions())
                            ->searchable()
                            ->required(),
                        Select::make('destination_iata')
                            ->label('Куда')
                            ->options(self::airportOptions())
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Даты')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('Дата вылета от')
                            ->required(),
                        DatePicker::make('date_to')
                            ->label('Дата вылета до')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Параметры поиска')
                    ->schema([
                        Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('search_type')
                            ->label('Тип поиска')
                            ->options(self::searchTypeOptions())
                            ->required()
                            ->default('round_trip'),
                    ])
                    ->columns(2),
                Section::make('Цены')
                    ->schema([
                        TextInput::make('min_price')
                            ->label('Мин цена')
                            ->numeric()
                            ->prefix('₽'),
                        TextInput::make('max_price')
                            ->label('Макс цена')
                            ->numeric()
                            ->prefix('₽'),
                        TextInput::make('median_price')
                            ->label('Медианная цена')
                            ->numeric()
                            ->prefix('₽'),
                    ])
                    ->columns(3),

                Section::make('Итог')
                    ->schema([
                        TextInput::make('results_count')
                            ->label('Кол-во резул')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Select::make('status')
                            ->label('Статус')
                            ->options(self::searchStatusOptions())
                            ->required()
                            ->default('completed'),
                        DateTimePicker::make('searched_at')
                            ->label('Дата'),
                    ])
                    ->columnSpanFull()
                    ->columns(3),

                Section::make('Сводка')
                    ->schema([
                        Textarea::make('request_payload')
                            ->label('Параметры запроса')
                            ->rows(10)
                            ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                            ->dehydrateStateUsing(fn (mixed $state): ?array => self::decodeJson($state))
                            ->columnSpanFull(),
                        Textarea::make('response_summary')
                            ->label('Сводка ответа')
                            ->rows(10)
                            ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                            ->dehydrateStateUsing(fn (mixed $state): ?array => self::decodeJson($state))
                            ->columnSpanFull(),
                        Textarea::make('provider_payload')
                            ->label('Результаты Travelpayouts')
                            ->rows(12)
                            ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                            ->dehydrateStateUsing(fn (mixed $state): ?array => self::decodeJson($state))
                            ->columnSpanFull(),
                        Textarea::make('error_message')
                            ->label('Сообщение об ошибке')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function airportOptions(): array
    {
        return Airport::query()
            ->where('is_active', true)
            ->orderBy('city')
            ->orderBy('iata_code')
            ->get()
            ->mapWithKeys(static fn (Airport $airport): array => [
                $airport->iata_code => sprintf('%s - %s', $airport->city, $airport->iata_code),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function searchTypeOptions(): array
    {
        return [
            'round_trip' => 'Туда и обратно',
            'one_way' => 'В одну сторону',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function searchStatusOptions(): array
    {
        return [
            'completed' => 'Завершён',
            'pending' => 'В обработке',
            'failed' => 'Ошибка',
        ];
    }

    private static function prettyJson(mixed $state): string
    {
        if ($state === null || $state === '') {
            return '';
        }

        if (is_string($state)) {
            $decoded = json_decode($state, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: $state;
            }

            return $state;
        }

        return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodeJson(mixed $state): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_array($state)) {
            return $state;
        }

        if (! is_string($state)) {
            throw new InvalidArgumentException('Некорректный JSON.');
        }

        try {
            $decoded = json_decode($state, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Некорректный JSON.');
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Некорректный JSON.');
        }

        return $decoded;
    }
}
