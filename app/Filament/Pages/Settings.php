<?php

namespace App\Filament\Pages;

use App\Models\IntegrationSetting;
use App\Models\ProfitabilitySetting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use BackedEnum;
use UnitEnum;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected string $view = 'filament.pages.settings';

    protected static ?string $title = 'Настройки';
    protected static ?string $navigationLabel = 'Настройки';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static UnitEnum|string|null $navigationGroup = 'Администрирование';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(array_merge(
            IntegrationSetting::current()->attributesToArray(),
            ProfitabilitySetting::current()->attributesToArray(),
        ));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Настройки')
                    ->tabs([
                        Tab::make('Настройки выгодности')
                            ->schema([
                                Section::make('Фиксация сигнала')
                                    ->schema([
                                        TextInput::make('signal_threshold_percent')
                                            ->label('От скольки процентов выгоды считать рейс сигналом')
                                            ->helperText('Например, 40 означает: если билет дешевле средней цены на 40% и больше, сигнал попадёт в записи. Рейсы дешевле 3000 ₽ тоже попадут в сигналы.')
                                            ->numeric()
                                            ->required()
                                            ->suffix('%')
                                            ->minValue(0)
                                            ->maxValue(100),
                                    ])
                                    ->columns(1),
                                Repeater::make('rules')
                                    ->label('Правила оценки. Задайте, сколько баллов давать за диапазон выгоды в процентах.')
                                    ->addActionLabel('Добавить правило')
                                    ->schema([
                                        TextInput::make('from_percent')
                                            ->label('От %')
                                            ->numeric()
                                            ->required(),
                                        TextInput::make('to_percent')
                                            ->label('До %')
                                            ->numeric(),
                                        TextInput::make('points')
                                            ->label('Баллы')
                                            ->numeric()
                                            ->required(),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Интеграции')
                            ->schema([
                                Section::make('Интеграции')
                                    ->schema([
                                        TextInput::make('travelpayouts_api_base')
                                            ->label('Travelpayouts API URL')
                                            ->url()
                                            ->required(),
                                        TextInput::make('travelpayouts_api_token')
                                            ->label('Travelpayouts API Токен')
                                            ->password()
                                            ->revealable()
                                            ->required(),
                                        TextInput::make('travelpayouts_partner_id')
                                            ->label('Travelpayouts partner ID')
                                            ->required(),
                                        TextInput::make('travelpayouts_tp_trs')
                                            ->label('Travelpayouts tp_trs')
                                            ->required(),
                                        TextInput::make('travelpayouts_tp_p')
                                            ->label('Travelpayouts tp_p')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        ProfitabilitySetting::current()->update([
            'signal_threshold_percent' => $data['signal_threshold_percent'] ?? 40,
            'rules' => $data['rules'] ?? [],
        ]);

        IntegrationSetting::current()->update(Arr::only($data, [
            'travelpayouts_api_base',
            'travelpayouts_api_token',
            'travelpayouts_partner_id',
            'travelpayouts_tp_trs',
            'travelpayouts_tp_p',
        ]));

        Notification::make()
            ->success()
            ->title('Настройки сохранены')
            ->body('Параметры выгодности и интеграций обновлены.')
            ->send();
    }
}
