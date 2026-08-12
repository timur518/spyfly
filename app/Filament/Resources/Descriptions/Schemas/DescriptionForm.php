<?php

namespace App\Filament\Resources\Descriptions\Schemas;

use App\Models\Airport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DescriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Пользователь и маршрут')
                    ->schema([
                        Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required(),
                        Select::make('trip_type')
                            ->label('Тип поездки')
                            ->options(self::tripTypeOptions())
                            ->required()
                            ->default('round_trip'),
                        Select::make('origin_iata')
                            ->label('Откуда')
                            ->options(self::airportOptions())
                            ->searchable()
                            ->required(),
                        Select::make('destination_iata')
                            ->label('Куда')
                            ->options(self::airportOptions())
                            ->searchable()
                            ->placeholder('Не ограничивать'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Section::make('Ограничения')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('Дата от')
                            ->native(false),
                        DatePicker::make('date_to')
                            ->label('Дата до')
                            ->native(false)
                            ->afterOrEqual('date_from'),
                        TextInput::make('max_desired_price')
                            ->label('Максимальная цена')
                            ->numeric()
                            ->prefix('₽'),
                        TextInput::make('min_stay_days')
                            ->label('Минимум дней')
                            ->numeric(),
                        TextInput::make('max_stay_days')
                            ->label('Максимум дней')
                            ->numeric(),
                    ])
                    ->columns(2),
                Section::make('Дополнительно')
                    ->schema([
                        DateTimePicker::make('last_notified_at')
                            ->label('Последнее уведомление'),
                        Select::make('channel')
                            ->label('Канал уведомления')
                            ->options(self::channelOptions())
                            ->required()
                            ->default('email'),
                        Toggle::make('is_active')
                            ->label('Активна')
                            ->required(),
                    ]),
                Section::make('Найденные рейсы')
                    ->description('Фоновая джоба обновляет это поле с подходящими рейсами в формате JSON.')
                    ->schema([
                        Textarea::make('matched_flights')
                            ->label('Рейсы')
                            ->rows(10)
                            ->disabled()
                            ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                            ->columnSpanFull(),
                    ]),
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
    private static function tripTypeOptions(): array
    {
        return [
            'round_trip' => 'Туда и обратно',
            'one_way' => 'В одну сторону',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function channelOptions(): array
    {
        return [
            'email' => 'Email',
            'telegram' => 'Telegram',
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
}
